<?php
namespace Wwwision\Eventr\Adapters\EventStore;

use EventStore\EventStore as EventStoreClient;
use EventStore\Exception\WrongExpectedVersionException as EventStoreWrongVersionException;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\EventStoreAdapterInterface;
use Wwwision\Eventr\EventStream;
use Wwwision\Eventr\ExpectedVersion;
use Wwwision\Eventr\WrongExpectedVersionException;

class EventStoreAdapter implements EventStoreAdapterInterface
{
    /**
     * @Flow\Inject
     * @var EventStoreClient
     */
    protected $eventStoreClient;

    /**
     * {@inheritDoc}
     */
    public function writeToStream($streamName, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY)
    {
        $metaData = $event->hasMetadata() ? $event->getMetadata() : [];
        $esEvent = \EventStore\WritableEvent::newInstance($event->getType(), $event->getData(), $metaData);

        try {
            $this->eventStoreClient->writeToStream($streamName, $esEvent, $expectedVersion);
        } catch (EventStoreWrongVersionException $exception) {
            throw new WrongExpectedVersionException($exception->getMessage(), $exception->getCode());
        }
    }

    /**
     * {@inheritDoc}
     */
    public function getEventStreamFor(AggregateType $aggregateType, $offset = 0)
    {
        $streamName = sprintf('$ce-%s', $aggregateType->getName());
        $streamIterator = new EventStoreStreamIterator($this->eventStoreClient, $streamName, $offset);
        return new EventStream($streamIterator);
    }
}