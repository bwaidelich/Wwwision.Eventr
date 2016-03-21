<?php
namespace Wwwision\Eventr\Domain\Dto;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\EventStore;
use Wwwision\Eventr\ExpectedVersion;

class Aggregate
{
    /**
     * @var AggregateType
     */
    private $type;

    /**
     * @var string
     */
    private $id;

    /**
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

    /**
     * @Flow\Inject(lazy=false)
     * @var Now
     */
    protected $now;

    /**
     * @param AggregateType $type
     * @param string $id
     */
    public function __construct(AggregateType $type, $id)
    {
        $this->type = $type;
        $this->id = $id;
    }

    /**
     * @return AggregateType
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param string $type
     * @param array $data
     * @param int $expectVersion
     * @param \DateTimeInterface $date The date this event has occurred. If null the current date will be used
     * @return void
     */
    public function emitEvent($type, array $data = [], $expectVersion = ExpectedVersion::ANY, \DateTimeInterface $date = null)
    {
        if ($date === null) {
            $date = $this->now;
        }
        $metadata = [
            'id' => $this->id,
            'date' => $date->format(DATE_ISO8601),
        ];
        $this->emit(new WritableEvent($type, $data, $metadata), $expectVersion);
    }

    /**
     * @param WritableEvent $event
     * @param int $expectVersion
     * @return void
     */
    public function emit(WritableEvent $event, $expectVersion = ExpectedVersion::ANY)
    {
        $eventType = $this->type->getEventType($event->getType());
        $eventType->validatePayload($event->getData());

        $this->eventStore->writeToAggregateStream($this, $event, $expectVersion);
    }

}