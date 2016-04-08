<?php
namespace Wwwision\Eventr\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use TYPO3\Flow\Cli\ConsoleOutput;
use TYPO3\Flow\Core\Bootstrap;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\Eventr;
use Wwwision\Eventr\WrongExpectedVersionException;

/**
 * Base class for eventr migrations, with convenience methods to register aggregate types, event types, projections and for migrating data
 */
abstract class AbstractEventrMigration extends AbstractMigration
{
    /**
     * @var ConsoleOutput
     */
    protected $output;

    /**
     * @var Eventr
     */
    protected $eventr;

    /**
     * @return bool
     */
    public function isTransactional()
    {
        return false;
    }

    /**
     * @param Eventr $eventr
     * @return void
     */
    public function injectEventr(Eventr $eventr)
    {
        $this->eventr = $eventr;
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function preUp(Schema $schema)
    {
        $objectManager = Bootstrap::$staticObjectManager;
        $this->eventr = $objectManager->get(Eventr::class);
        $this->output = new ConsoleOutput();
    }

	/**
	 * @param string $name
	 * @return AggregateType
	 */
	protected function registerOrGetAggregateType($name) {
		$this->output->outputLine('  Registering AggregateType "%s".', [$name]);
		try {
			return $this->eventr->registerAggregateType($name);
		} catch (\InvalidArgumentException $exception) {
			$this->output->outputLine('    AggregateType "%s" was already registered, reusing existing one.', [$name]);
			return $this->eventr->getAggregateType($name);
		}
	}

	/**
	 * @param AggregateType $aggregateType
	 * @param string $eventName
	 * @param string $eventSchema
	 */
	protected function registerOrUpdateEventType(AggregateType $aggregateType, $eventName, $eventSchema = NULL) {
		try {
			$aggregateType->registerEventType($eventName, $eventSchema);
			$this->output->outputLine('    Registered EventType "%s.%s".', [$aggregateType->getName(), $eventName]);
		} catch (\InvalidArgumentException $exception) {
			$aggregateType->updateEventType($eventName, $eventSchema);
			$this->output->outputLine('    EventType "%s.%s" already registered, updating schema.', [$aggregateType->getName(), $eventName]);
		}
	}

	/**
	 * @param string $projectionName
	 * @param AggregateType $aggregateType
	 * @param array $mapping
	 * @param array $adapterConfiguration
	 */
	protected function registerOrUpdateProjection($projectionName, AggregateType $aggregateType, array $mapping, array $adapterConfiguration) {
		try {
			$this->eventr->registerProjection($projectionName, $aggregateType, $mapping, $adapterConfiguration);
			$this->output->outputLine('    Registered Projection "%s".', [$projectionName]);
		} catch (\InvalidArgumentException $exception) {
			$projection = $this->eventr->getProjection($projectionName);
			if ($projection->getAggregateType() !== $aggregateType) {
				throw new \InvalidArgumentException(sprintf('Projection "%s" is registered for AggregateType "%s" and can\'t be updated to AggregateType "%s"!', $projection->getName(), $projection->getAggregateType(), $aggregateType), 1457373363);
			}
			$this->eventr->updateProjection($projectionName, $mapping, $adapterConfiguration);
			$this->output->outputLine('    Projection "%s" already registered, updating.', [$projectionName]);
		}
	}

    /**
     * @param string $query
     * @param string $aggregateName
     * @param string $eventType
     * @param \Closure $dataCallback
     * @return void
     */
    protected function migrateEventsFromDatabase($query, $aggregateName, $eventType, \Closure $dataCallback = null)
    {
        $countQuery = preg_replace('/SELECT[\s\S]+FROM/m', 'SELECT COUNT(*) FROM', $query, 1);
        $numberTotal = (integer)$this->connection->executeQuery($countQuery)->fetchColumn(0);

        $this->output->outputLine('  Migrating %d records into "%s" event stream:', [$numberTotal, $aggregateName]);
        $this->output->progressStart($numberTotal);

        $numberImported = 0;
        $numberFailed = 0;
        $statement = $this->connection->executeQuery($query);
        /** @noinspection PhpAssignmentInConditionInspection */
        while ($eventData = $statement->fetch(\PDO::FETCH_ASSOC)) {
            $aggregateId = $eventData['id'];
            unset($eventData['id']);
            $date = NULL;
            if (isset($eventData['date'])) {
                $date = new \DateTimeImmutable($eventData['date']);
                unset($eventData['date']);
            }
            if ($dataCallback !== null) {
                $eventData = $dataCallback($eventData);
            }

            $eventMetadata = [
                'date' => $date,
            ];
            try {
                $this->eventr->getAggregate($aggregateName, $aggregateId)->recordEvent($eventType, $eventData, $eventMetadata);
                $numberImported ++;
            } catch (WrongExpectedVersionException $exception) {
                $numberFailed ++;
            }
            $this->output->progressAdvance();
        }
        $this->output->progressFinish();
        $this->output->output('Done. ');
        if ($numberImported === 0) {
            $this->output->output('Imported: 0');
        } else {
            $this->output->output('<success>Imported: %d</success>', [$numberImported]);
        }
        if ($numberFailed === 0) {
            $this->output->output(', Skipped: 0');
        } else {
            $this->output->output(', <error>Skipped: %d</error>', [$numberFailed]);
        }
        $this->output->outputLine();
    }

}