<?php
namespace Wwwision\Eventr\Domain\Dto;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Neos\Utility\PositionalArraySorter;
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
     * @var ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @Flow\InjectConfiguration(path="eventHandlers.preprocess")
     * @var array
     */
    protected $preProcessEventHandlers;

    /**
     * @Flow\InjectConfiguration(path="eventHandlers.postprocess")
     * @var array
     */
    protected $postProcessEventHandlers;

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

        $this->handleEvent($this->preProcessEventHandlers, $event);

        $writtenEvent = $this->eventStore->writeToAggregateStream($this, $event, $expectVersion);

        $this->handleEvent($this->postProcessEventHandlers, $writtenEvent);
    }

    /**
     * @param array $handlerConfigurations
     * @param EventInterface $event
     * @return void
     */
    private function handleEvent(array $handlerConfigurations, EventInterface $event)
    {
        foreach ((new PositionalArraySorter($handlerConfigurations))->toArray() as $handlerName => $handlerConfiguration) {
            if (!$this->handlerShouldBeTriggered($handlerConfiguration, $event)) {
                continue;
            }
            /** @var EventHandlerInterface $handler */
            $handler = $this->objectManager->get($handlerConfiguration['handlerClassName']);
            $handler->handle($this, $event);
        }
    }

    /**
     * @param array $handlerConfiguration
     * @param EventInterface $event
     * @return bool
     */
    private function handlerShouldBeTriggered(array $handlerConfiguration, EventInterface $event) {
        if (!isset($handlerConfiguration['events'])) {
            return true;
        }
        foreach ($handlerConfiguration['events'] as $pattern => $enabled) {
            if (!$enabled) {
                continue;
            }
            list($matchingAggregateType, $matchingEventType) = explode('.', $pattern);
            if ($matchingAggregateType !== '*' && $matchingAggregateType !== $this->getType()->getName()) {
                continue;
            }
            if ($matchingEventType !== '*' && $matchingEventType !== $event->getType()) {
                continue;
            }
            return true;
        }
        return false;
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
        $this->eventStore->writeToAggregateStream($this, $event);
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

    /**
     * @param Aggregate $aggregate
     * @param Event $event
     * @return void
     * @Flow\Signal
     */
    protected function emitAfterEventPublished(Aggregate $aggregate, Event &$event)
    {
    }

}