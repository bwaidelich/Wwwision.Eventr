<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;

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
     * @param Aggregate $aggregate
     * @param WritableEvent $event
     * @param int $expectedVersion
     * @return void
     */
    public function writeToAggregateStream(Aggregate $aggregate, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY)
    {
        $streamName = sprintf('%s-%s', $aggregate->getType(), $aggregate->getId());
        $this->eventStoreAdapter->writeToStream($streamName, $event, $expectedVersion);
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
}