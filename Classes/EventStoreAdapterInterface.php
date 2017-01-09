<?php
namespace Wwwision\Eventr;

use Neos\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;

interface EventStoreAdapterInterface
{

    /**
     * @param string $streamName
     * @param WritableEvent $event
     * @param int $expectedVersion
     * @return Event
     */
    public function writeToStream($streamName, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY);

    /**
     * @param AggregateType $aggregateType
     * @param int $offset
     * @return EventStream
     */
    public function getEventStreamFor(AggregateType $aggregateType, $offset = 0);
}