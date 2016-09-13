<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Dto\ProjectionConfiguration;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;
use Wwwision\Eventr\EventHandler\EventHandlerInterface;
use Wwwision\Eventr\EventStore;
use Wwwision\Eventr\GetAggregateIdFromEventInterface;
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
     * @ORM\Column(nullable = TRUE)
     * @var integer
     */
    protected $batchSize = 0;


    /**
     * @Flow\Transient
     * @var ProjectionHandlerInterface
     */
    protected $handler;

    /**
     * @Flow\Transient
     * @Flow\Inject
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @Flow\Transient
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
        $this->batchSize = $configuration->batchSize;
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
     * @return int
     */
    public function getBatchSize()
    {
        return $this->batchSize;
    }

    /**
     * @param int $batchSize
     */
    public function updateBatchSize($batchSize)
    {
        $this->batchSize = $batchSize;
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
        $projectionHandler = $this->getHandler();
        $eventStream->onAny(function (Event $event) use ($projectionHandler) {
            if ($projectionHandler instanceof GetAggregateIdFromEventInterface) {
                $aggregateId = $projectionHandler->getAggregateIdFromEvent($event);
            } else {
                $aggregateId = $event->getMetadata()['id'];
            }
            $projectionHandler->handle($this->aggregateType->getAggregate($aggregateId), $event);
        });

        // When the version does not change anymore, we are finished with the chunks
        $previousVersion = -1;
        while ($previousVersion != $this->version) {
            $previousVersion = $this->version;
            $this->version = $eventStream->replay($this->batchSize);
            $this->projectionRepository->update($this);
            $this->persistenceManager->whitelistObject($this);
            $this->persistenceManager->persistAll();
        }

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