<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Jobqueue\Common\Job\JobManager;
use TYPO3\Jobqueue\Common\Job\StaticMethodCallJob;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

/**
 * @Flow\Scope("singleton")
 */
class EventStore
{

    /**
     * @Flow\Inject
     * @var EventStoreAdapterInterface
     */
    protected $eventStoreAdapter;

    /**
     * @Flow\Inject
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @param Aggregate $aggregate
     * @param WritableEvent $event
     * @param int $expectedVersion
     * @return void
     */
    public function writeToAggregateStream(Aggregate $aggregate, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY)
    {
        $streamName = sprintf('%s-%s', $aggregate->getType(), $aggregate->getId());

        $this->emitBeforeEventWritten($aggregate, $event);
        $this->eventStoreAdapter->writeToStream($streamName, $event, $expectedVersion);

        // TODO async event handler?
        $this->emitEventWritten($aggregate, $event);
        // TODO use sync/async EventPublisher somehow
        // TODO only replay projections matching stream/event
        foreach ($this->projectionRepository->findSynchronousByAggregateType($aggregate->getType()) as $projection) {
            $projection->replay();
        }
        $this->jobManager->queue('eventr-projection', new StaticMethodCallJob(get_class($this), 'catchupProjections', [$aggregate->getType()]));
    }

    /**
     * @param AggregateType $aggregateType
     * @return void
     */
    public function catchupProjections(AggregateType $aggregateType)
    {
        foreach ($this->projectionRepository->findAsynchronousByAggregateType($aggregateType) as $projection) {
            $projection->replay();
        }
    }

    /**
     * @param AggregateType $aggregateType
     * @param int $offset
     * @return EventStream
     */
    public function getEventStreamFor(AggregateType $aggregateType, $offset = 0)
    {
        return $this->eventStoreAdapter->getEventStreamFor($aggregateType, $offset);
    }

    /**
     * @param Aggregate $aggregate
     * @param WritableEvent $event
     * @return void
     * @Flow\Signal
     */
    protected function emitBeforeEventWritten(Aggregate $aggregate, WritableEvent &$event)
    {
    }

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     * @Flow\Signal
     */
    protected function emitEventWritten(Aggregate $aggregate, EventInterface $event)
    {
    }
}