<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Dto\ProjectionConfiguration;
use Wwwision\Eventr\EventHandler\EventHandlerInterface;
use Wwwision\Eventr\EventStore;
use Wwwision\Eventr\ProjectionHandlerInterface;

/**
 * @Flow\Entity
 * @ORM\Table(name="eventr_projections")
 */
class Projection implements EventHandlerInterface
{
    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

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
     * @var string
     */
    protected $handlerClassName;

    /**
     * @ORM\Column(type="json_array")
     * @var array
     */
    protected $handlerOptions = array();

    /**
     * @Flow\Transient
     * @var ProjectionHandlerInterface
     */
    protected $handler;

    /**
     * @param string $name
     * @param ProjectionConfiguration $configuration
     */
    public function __construct($name, ProjectionConfiguration $configuration)
    {
        $this->name = $name;
        $this->aggregateType = $configuration->aggregateType;
        if (!is_a($configuration->handlerClassName, ProjectionHandlerInterface::class, true)) {
            throw new \InvalidArgumentException(sprintf('Handler className must refer to a class implementing the ProjectionHandlerInterface, got "%s"', $configuration->handlerClassName), 1456741361);
        }
        $this->handlerClassName = $configuration->handlerClassName;
        $this->handlerOptions = $configuration->handlerOptions;
        $this->synchronous = $configuration->synchronous;
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
     * @return string
     */
    public function getHandlerClassName()
    {
        return $this->handlerClassName;
    }

    /**
     * @param string $handlerClassName
     * @return void
     */
    public function updateHandlerClassName($handlerClassName)
    {
        $this->handlerClassName = $handlerClassName;
    }

    /**
     * @return array
     */
    public function getHandlerOptions()
    {
        return $this->handlerOptions;
    }

    /**
     * @param array $handlerOptions
     * @return void
     */
    public function updateHandlerOptions(array $handlerOptions)
    {
        $this->handlerOptions = $handlerOptions;
    }

    /**
     * @return ProjectionHandlerInterface
     */
    private function getHandler()
    {
        if ($this->handler === null) {
            $this->handler = new $this->handlerClassName($this->handlerOptions);
        }
        return $this->handler;
    }

    /**
     * Resets the projection state and replays the event stream from the beginning
     *
     * @return void
     */
    public function replay()
    {
        $this->getHandler()->resetState();
        $this->version = 0;
        $this->replayStream();
    }

    public function catchup()
    {
        $this->replayStream($this->version);
    }

    private function replayStream($offset = 0)
    {
        $eventStream = $this->aggregateType->getEventStream($offset);
        $eventStream->onAny(function (Event $event) {
            $aggregateId = $event->getMetadata()['id'];
            $this->getHandler()->handle($this->aggregateType->getAggregate($aggregateId), $event);
        });
        $this->version = $eventStream->replay();
    }

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        $this->getHandler()->handle($aggregate, $event);
    }
}