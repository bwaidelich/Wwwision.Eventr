<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;

/**
 */
final class EventStream
{
    /**
     * @var array
     */
    private $eventCallbacks = [];

    /**
     * @var array
     */
    private $anyEventCallbacks = [];

    /**
     * @var EventStreamIteratorInterface
     */
    private $streamIterator;

    /**
     * @param EventStreamIteratorInterface $streamIterator
     */
    public function __construct(EventStreamIteratorInterface $streamIterator)
    {
        $this->streamIterator = $streamIterator;
    }

    /**
     * @return EventStreamIteratorInterface
     */
    public function getStreamIterator()
    {
        return $this->streamIterator;
    }

    /**
     * @param \Closure $onEventCallback
     * @return void
     */
    public function onAny(\Closure $onEventCallback)
    {
        $this->anyEventCallbacks[] = $onEventCallback;
    }

    /**
     * @param string $eventType
     * @param \Closure $onEventCallback
     * @return void
     */
    public function on($eventType, \Closure $onEventCallback)
    {
        if (!isset($this->eventCallbacks[$eventType])) {
            $this->eventCallbacks[$eventType] = [];
        }
        $this->eventCallbacks[$eventType][] = $onEventCallback;
    }

    /**
     * @return string
     */
    public function replay()
    {
        /** @var Event $event */
        foreach ($this->streamIterator as $event) {
            foreach ($this->anyEventCallbacks as $callback) {
                call_user_func($callback, $event, $this->streamIterator->key());
            }
            if (!isset($this->eventCallbacks[$event->getType()])) {
                continue;
            }
            foreach ($this->eventCallbacks[$event->getType()] as $callback) {
                call_user_func($callback, $event, $this->streamIterator->key());
            }
        }
    }

}