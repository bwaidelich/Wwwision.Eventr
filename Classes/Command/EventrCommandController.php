<?php
namespace Wwwision\Eventr\Command;

use TYPO3\Eel\CompilingEvaluator;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Cli\CommandController;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Utility\Algorithms;
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
        $this->eventr->registerProjection($name, $aggregateType, $mapping, $adapterConfiguration);
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
        $this->outputLine('Adapter Configuration:');
        $this->outputLine('<i>%s</i>', [json_encode($projection->getAdapterConfiguration(), JSON_PRETTY_PRINT)]);
        $this->outputLine('Mapping:');
        $this->outputLine('<i>%s</i>', [json_encode($projection->getMapping(), JSON_PRETTY_PRINT)]);
    }

    /**
     * @param string $projection
     * @param int $offset
     * @return void
     */
    public function projectionReplayCommand($projection, $offset = 0)
    {
        $projection = $this->eventr->getProjection($projection);
        $this->outputLine('Replaying projection "%s" from version %d', [$projection->getName(), $offset]);
        $projection->replay($offset);
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
        $projection->replay();
        $this->outputLine('Done.');
    }
}