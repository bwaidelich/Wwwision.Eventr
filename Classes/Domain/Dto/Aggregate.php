<?php
namespace Wwwision\Eventr\Domain\Dto;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Object\ObjectManagerInterface;
use TYPO3\Flow\Reflection\ReflectionService;
use TYPO3\Flow\Utility\Now;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\EventHandler\EventHandlerInterface;
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
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var ObjectManagerInterface
     */
    protected $objectManager;

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
     * @return void
     */
    public function emitEvent($type, array $data = [], $expectVersion = ExpectedVersion::ANY)
    {
        $event = new WritableEvent($type, $data);
        $this->addDefaultEventMetadata($event);

        // TODO introduce event bus!?
        $this->emitBeforeEventPublished($this, $event);

        $eventType = $this->type->getEventType($event->getType());
        $eventType->validatePayload($event->getData());

        $this->eventStore->writeToAggregateStream($this, $event, $expectVersion);

        // TODO eehk, refactor (event bus?)
        foreach ($this->reflectionService->getAllImplementationClassNamesForInterface(EventHandlerInterface::class) as $eventHandlerClassName) {
            /** @var EventHandlerInterface $eventHandler */
            $eventHandler = $this->objectManager->get($eventHandlerClassName);
            $eventHandler->handle($this, $event);
        }
    }

    /**
     * @param string $type
     * @param array $data
     * @param array $metadata
     * @return void
     */
    public function recordEvent($type, array $data = [], array $metadata = null)
    {
        $event = new WritableEvent($type, $data, $metadata);
        $this->addDefaultEventMetadata($event);
        $this->eventStore->writeToAggregateStream($this, $event);
    }

    /**
     * @param WritableEvent $event
     * @return void
     */
    private function addDefaultEventMetadata(WritableEvent $event)
    {
        if (!$event->hasMetadataKey('id')) {
            $event->addMetadata('id', $this->id);
        }
        if (!$event->hasMetadataKey('date')) {
            $event->addMetadata('date', $this->now->format(DATE_ISO8601));
        }
    }

    /**
     * @param Aggregate $aggregate
     * @param WritableEvent $event
     * @return void
     * @Flow\Signal
     */
    protected function emitBeforeEventPublished(Aggregate $aggregate, WritableEvent &$event)
    {
    }


}