<?php
namespace Wwwision\Eventr\Command;

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use TYPO3\Flow\Utility\Algorithms;
use Wwwision\Eventr\Domain\Dto\ProjectionConfiguration;
use Wwwision\Eventr\Eventr;
use Wwwision\Eventr\ExpectedVersion;

/**
 * @Flow\Scope("singleton")
 */
class EventrCommandController extends CommandController
{

    /**
     * @Flow\Inject
     * @var CompilingEvaluator
     */
    protected $eelEvaluator;

    /**
     * @Flow\Inject
     * @var Eventr
     */
    protected $eventr;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @param string $aggregateType
     * @return void
     */
    public function registerAggregateTypeCommand($aggregateType)
    {
        $this->eventr->registerAggregateType($aggregateType);
        $this->outputLine('AggregateType "%s" registered!', [$aggregateType]);
    }

    /**
     * @param string $aggregateType
     * @param string $eventType
     * @param string $schema
     * @return void
     */
    public function registerEventTypeCommand($aggregateType, $eventType, $schema)
    {
        $aggregateType = $this->eventr->getAggregateType($aggregateType);
        if ($schema === '') {
            $schema = null;
        } else {
            $schema = json_decode($schema, true);
            if ($schema === null) {
                $this->outputLine('<error>Schema must be empty or a valid JSON string</error>');
                $this->quit(1);
            }
        }
        $aggregateType->registerEventType($eventType, $schema);
        $this->outputLine('EventType "%s.%s" registered!', [$aggregateType->getName(), $eventType]);
    }

    /**
     * @param string $aggregateType
     * @param string $eventType
     * @param string $id
     * @param string $payload as JSON string
     * @param string $expectVersion the expected version as number or "any" or "no-stream"
     * @return void
     */
    public function emitEventCommand($aggregateType, $eventType, $id = null, $payload = null, $expectVersion = 'any')
    {
        if ($id === null) {
            $id = Algorithms::generateUUID();
        }
        $aggregate = $this->eventr->getAggregate($aggregateType, $id);

        if ($expectVersion === 'any') {
            $expectVersion = ExpectedVersion::ANY;
        } elseif ($expectVersion === 'no-stream') {
            $expectVersion = ExpectedVersion::NO_STREAM;
        } else {
            $expectVersion = (integer)$expectVersion;
        }
        $payload = $payload === null ? [] : json_decode($payload, true);
        $aggregate->emitEvent($eventType, $payload, $expectVersion);
        $this->outputLine('Event "%s" emitted for aggregate "%s" with id "%s"', [$eventType, $aggregateType, $id]);
    }

    /**
     * @param string $name
     * @param string $aggregateType
     * @param string $handlerClassName
     * @param string $handlerOptions as JSON string
     * @param boolean $synchronous
     * @return void
     */
    public function registerProjectionCommand($name, $aggregateType, $handlerClassName, $handlerOptions = null, $synchronous = false)
    {
        $aggregateType = $this->eventr->getAggregateType($aggregateType);
        $configuration = new ProjectionConfiguration($aggregateType, $handlerClassName);
        if ($handlerOptions !== null) {
            $handlerOptions = json_decode($handlerOptions, true);
            if ($handlerOptions === null) {
                $this->outputLine('<error>The handlerOptions argument is no valid JSON string</error>');
                $this->quit(1);
            }
            $configuration->handlerOptions = $handlerOptions;
        }
        $configuration->synchronous = $synchronous;
        $this->eventr->registerProjection($name, $configuration);
        $this->outputLine('Projection "%s" registered!', [$name]);
    }

    /**
     * @return void
     */
    public function listAggregateTypesCommand()
    {
        $rows = [];
        foreach ($this->eventr->getAggregateTypes() as $aggregateType) {
            $rows[] = [$aggregateType->getName(), count($aggregateType->getEventTypes())];
        }
        $this->output->outputTable($rows, ['Aggregate Type', '# Event Types']);
    }

    /**
     * @param string $aggregateType
     * @return void
     */
    public function showAggregateTypeCommand($aggregateType)
    {
        $aggregateType = $this->eventr->getAggregateType($aggregateType);
        $this->outputLine('<b>%s</b>', [$aggregateType->getName()]);
        $this->outputLine('EventTypes:');
        $rows = [];
        foreach ($aggregateType->getEventTypes() as $eventType) {
            $rows[] = [$eventType->getName(), $eventType->hasSchema() ? '✔' : '-'];
        }
        $this->output->outputTable($rows, ['EventType', 'Schema?']);
    }

    /**
     * @param string $aggregateType
     * @param string $eventType
     * @return void
     */
    public function showEventTypeCommand($aggregateType, $eventType)
    {
        $aggregateType = $this->eventr->getAggregateType($aggregateType);
        $eventType = $aggregateType->getEventType($eventType);
        $this->outputLine('Schema of event <b>%s.%s</b>', [$aggregateType->getName(), $eventType->getName()]);
        if ($eventType->hasSchema()) {
            $this->outputLine('Schema:');
            $this->outputLine('<i>%s</i>', [json_encode($eventType->getSchema(), JSON_PRETTY_PRINT)]);
        } else {
            $this->outputLine('(EventType has no schema)');
        }
    }

    /**
     * @return void
     */
    public function listProjectionsCommand()
    {
        $rows = [];
        foreach ($this->eventr->getProjections() as $projection) {
            $rows[] = [$projection->getName(), $projection->getAggregateType()->getName()];
        }
        $this->output->outputTable($rows, ['Projection', 'AggregateType']);
    }

    /**
     * @param string $projection
     * @return void
     */
    public function showProjectionCommand($projection)
    {
        $projection = $this->eventr->getProjection($projection);
        $this->outputLine('<b>%s</b>', [$projection->getName()]);
        $this->outputLine('AggregateType: <b>%s</b>', [$projection->getAggregateType()->getName()]);
        $this->outputLine('Handler class name:');
        $this->outputLine('<i>%s</i>', [$projection->getHandlerClassName()]);
        $this->outputLine('Handler options:');
        $this->outputLine('<i>%s</i>', [json_encode($projection->getHandlerOptions(), JSON_PRETTY_PRINT)]);
    }

    /**
     * @param string $projection
     * @return void
     */
    public function projectionReplayCommand($projection)
    {
        $projection = $this->eventr->getProjection($projection);
        $this->outputLine('Replaying projection "%s"', [$projection->getName()]);
        $projection->replay();
        $this->persistenceManager->persistAll();
        $this->outputLine('Done.');
    }

    /**
     * @param string $projection
     * @return void
     */
    public function projectionCatchupCommand($projection)
    {
        $projection = $this->eventr->getProjection($projection);
        $this->outputLine('Catching up projection "%s" from version %d', [$projection->getName(), $projection->getVersion()]);
        $projection->catchup();
        $this->outputLine('Done.');
    }
}