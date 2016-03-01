<?php
namespace Wwwision\Eventr\Domain\Model;

use EventStore\EventStore;
use TYPO3\Eel\CompilingEvaluator as EelEvaluator;
use TYPO3\Eel\Context as EelContext;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\EventStream;
use Wwwision\Eventr\ProjectionAdapterInterface;

/**
 * @Flow\Entity
 */
class Projection
{
    /**
     * @ORM\Id
     * @var string
     */
    protected $name;

    /**
     * @ORM\ManyToOne
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @ORM\Column(type="json_array")
     * @var array
     */
    protected $mapping;

    /**
     * @ORM\Column(type="json_array")
     * @var array
     */
    protected $adapterConfiguration;

    /**
     * @Flow\Transient
     * @var array
     */
    protected $stateCache = [];

    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @Flow\Inject
     * @var EelEvaluator
     */
    protected $eelEvaluator;

    /**
     * @param string $name
     * @param AggregateType $aggregateType
     * @param array $mapping
     * @param array $adapterConfiguration
     */
    public function __construct($name, AggregateType $aggregateType, array $mapping, array $adapterConfiguration)
    {
        $this->name = $name;
        $this->aggregateType = $aggregateType;
        $this->mapping = $mapping;
        if (!isset($adapterConfiguration['className']) || !is_a($adapterConfiguration['className'], ProjectionAdapterInterface::class, true)) {
            throw new \InvalidArgumentException('Missing adapter className', 1456741361);
        }
        $this->adapterConfiguration = $adapterConfiguration;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return AggregateType
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @return array
     */
    public function getAdapterConfiguration()
    {
        return $this->adapterConfiguration;
    }

    /**
     * @return ProjectionAdapterInterface
     */
    private function getAdapter()
    {
        $handlerOptions = isset($this->adapterConfiguration['options']) ? $this->adapterConfiguration['options'] : [];
        return new $this->adapterConfiguration['className']($handlerOptions);
    }

    public function listen($offset = 0)
    {
        // HACK (specific to ES)
        $streamName = sprintf('$ce-%s', $this->aggregateType->getName());
        $eventStream = new EventStream($streamName, $this->eventStore->getUrl());

        $adapter = $this->getAdapter();

        $this->stateCache = [];
        $eelContext = new EelContext(['state' => []]);

        // TODO configurable helpers: $eelContext->push(new StringHelper(), 'String');

        foreach ($this->mapping as $eventName => $expression) {
            // HACK
            if ($eventName === '$init') {
                continue;
            }
            $eventStream->on($eventName, function(Event $event) use ($eelContext, $adapter) {
                $eelContext->push($event, 'event');
                $aggregateId = (string)$event->getData()['id'];
                if (!isset($this->stateCache[$aggregateId])) {
                    #\TYPO3\Flow\var_dump('fetching state for "' . $aggregateId . '"');
                    $state = $adapter->findById(new AggregateId($aggregateId));
                    if ($state === null) {
                        $state = [];
                    }
                    $this->stateCache[$aggregateId] = $state;
                }
                foreach ($this->mapping[$event->getType()] as $key => $expression) {
                    $eelContext->push($this->stateCache[$aggregateId], 'state');

                    $this->stateCache[$aggregateId][$key] = $this->eelEvaluator->evaluate($expression, $eelContext);
                }
                $this->stateCache[$aggregateId]['version'] = $event->getVersion();
                $adapter->project(new AggregateId($aggregateId), $this->stateCache[$aggregateId], $event);
            });
        }


        $eventStream->listen($offset);
    }

}