<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Eel\CompilingEvaluator as EelEvaluator;
use TYPO3\Eel\Context as EelContext;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\EventStore;
use Wwwision\Eventr\ProjectionAdapterInterface;

/**
 * @Flow\Entity
 * @ORM\Table(name="eventr_projections")
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
     * @var bool
     */
    protected $synchronous;

    /**
     * @var int
     */
    protected $version = 0;

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
     * @param bool $synchronous
     */
    public function __construct($name, AggregateType $aggregateType, array $mapping, array $adapterConfiguration, $synchronous = false)
    {
        $this->name = $name;
        $this->aggregateType = $aggregateType;
        $this->mapping = $mapping;
        if (!isset($adapterConfiguration['className']) || !is_a($adapterConfiguration['className'], ProjectionAdapterInterface::class, true)) {
            throw new \InvalidArgumentException('Missing adapter className', 1456741361);
        }
        $this->adapterConfiguration = $adapterConfiguration;
        $this->synchronous = $synchronous;
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
     * @return bool
     */
    public function isSynchronous()
    {
        return $this->synchronous;
    }

    /**
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @return array
     */
    public function getMapping()
    {
        return $this->mapping;
    }

    /**
     * @param array $mapping
     * @return void
     */
    public function updateMapping(array $mapping)
    {
        $this->mapping = $mapping;
    }

    /**
     * @return array
     */
    public function getAdapterConfiguration()
    {
        return $this->adapterConfiguration;
    }

    /**
     * @param array $adapterConfiguration
     * @return void
     */
    public function updateAdapterConfiguration(array $adapterConfiguration)
    {
        $this->adapterConfiguration = $adapterConfiguration;
    }

    /**
     * @return ProjectionAdapterInterface
     */
    private function getAdapter()
    {
        $handlerOptions = isset($this->adapterConfiguration['options']) ? $this->adapterConfiguration['options'] : [];
        return new $this->adapterConfiguration['className']($handlerOptions);
    }

    public function replay($offset = null)
    {
        if ($offset === null) {
            $offset = $this->version;
        }
        $eventStream = $this->eventStore->getEventStreamFor($this->aggregateType, $offset);
        foreach ($this->mapping as $eventType => $expression) {
            $eventStream->on($eventType, function (Event $event, $version) {
                $this->handle($event);
                $this->version = $version;
            });
        }
        $eventStream->replay();
    }

    public function handle(Event $event)
    {
        $aggregateId = (string)$event->getMetadata()['id'];
        $baseState = $this->getAdapter()->findById($aggregateId);
        $state = [];
        // TODO initial state (maybe defer to adapter: "state.foo + 1" => "`table.foo` + 1"
        // TODO configurable helpers: $eelContext->push(new StringHelper(), 'String');
        $eelContext = new EelContext(['state' => $baseState]);
        $eelContext->push($event, 'event');

        foreach ($this->mapping[(string)$event->getType()] as $propertyName => $expression) {
            $state[$propertyName] = $this->map($eelContext, $state, $expression);
        }
        $this->getAdapter()->project($aggregateId, $state, $event);
    }


    private function map($eelContext, $state, $expression)
    {
        if (is_array($expression)) {
            $subState = [];
            foreach ($expression as $subPropertyName => $subExpression) {
                $subState[$subPropertyName] = $this->map($eelContext, $state, $subExpression);
            }
            return $subState;
        }
        $eelContext->push($state, 'state');
        return $this->eelEvaluator->evaluate($expression, $eelContext);
    }

}