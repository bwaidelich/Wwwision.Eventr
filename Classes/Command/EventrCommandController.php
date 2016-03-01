<?php
namespace Wwwision\Eventr\Command;

use EventStore\ExpectedVersion;
use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Model\Projection;
use Wwwision\Eventr\Eventr;

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
     * @param int $expectVersion
     * @return void
     */
    public function emitEventCommand($aggregateType, $eventType, $id = null, $payload = null, $expectVersion = ExpectedVersion::ANY)
    {
        if ($id === null) {
            $id = AggregateId::create();
        } else {
            $id = new AggregateId($id);
        }
        $aggregate = $this->eventr->getAggregate($aggregateType, $id);

        $payload = $payload === null ? [] : json_decode($payload, true);
        $aggregate->emitEvent($eventType, $payload, $expectVersion);
        $this->outputLine('Event "%s" emitted for aggregate "%s" with id "%s"', [$eventType, $aggregateType, $id]);
    }

    /**
     * @param string $name
     * @param string $aggregateType
     * @param string $mapping as JSON string
     * @param string $adapter as JSON string
     * @return void
     */
    public function registerProjectionCommand($name, $aggregateType, $mapping, $adapter)
    {
        $aggregateType = $this->eventr->getAggregateType($aggregateType);
        $mapping = json_decode($mapping, true);
        if ($mapping === null) {
            $this->outputLine('<error>The mapping is no valid JSON string</error>');
            $this->quit(1);
        }
        $adapterConfiguration = json_decode($adapter, true);
        if ($adapterConfiguration === null) {
            $this->outputLine('<error>The adapter is no valid JSON string</error>');
            $this->quit(1);
        }
        $projection = new Projection($name, $aggregateType, $mapping, $adapterConfiguration);
        $this->eventr->registerProjection($projection);
        $this->outputLine('Projection "%s" registered!', [$name]);
    }

    /**
     * @param string $name
     * @return void
     */
    public function runProjectionCommand($name)
    {
        $projection = $this->eventr->getProjection($name);
        $this->outputLine('Running projection "%s"...', [$projection->getName()]);
        $projection->listen();
    }
}