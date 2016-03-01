<?php
namespace Wwwision\Eventr\Domain\Dto;

use Doctrine\ORM\Mapping as ORM;
use EventStore\EventStore;
use EventStore\ExpectedVersion;
use EventStore\WritableEvent;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Model\AggregateType;

class Aggregate
{
    /**
     * @var AggregateType
     */
    private $type;

    /**
     * @var AggregateId
     */
    private $id;

    /**
     * @var EventStore
     */
    private $eventStore;

    /**
     * @param AggregateType $type
     * @param AggregateId $id
     */
    public function __construct(AggregateType $type, AggregateId $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @param EventStore $eventStore
     * @return void
     */
    public function injectEventStore(EventStore $eventStore)
    {
        $this->eventStore = $eventStore;
    }

    /**
     * @param string $eventName
     * @param array $payload
     * @param int $expectVersion
     */
    public function emitEvent($eventName, array $payload = [], $expectVersion = ExpectedVersion::ANY)
    {
        $eventType = $this->type->getEventType($eventName);
        $eventType->validatePayload($payload);

        $payload['id'] = (string)$this->id;
        $streamName = sprintf('%s-%s', $this->type->getName(), $this->id);
        $event = WritableEvent::newInstance($eventName, $payload);
        $this->eventStore->writeToStream($streamName, $event, $expectVersion);
    }

}