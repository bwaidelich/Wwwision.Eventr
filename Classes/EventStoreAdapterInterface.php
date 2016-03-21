<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;

interface EventStoreAdapterInterface
{

    /**
     * @param string $streamName
     * @param WritableEvent $event
     * @param int $expectedVersion
     * @return void
     */
    public function writeToStream($streamName, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY);

    /**
     * @param AggregateType $aggregateType
     * @param int $offset
     * @return EventStream
     */
    public function getEventStreamFor(AggregateType $aggregateType, $offset = 0);
}