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
     * @var \Iterator
     */
    private $streamIterator;

    /**
     * @param \Iterator $streamIterator
     */
    public function __construct(\Iterator $streamIterator)
    {
        $this->streamIterator = $streamIterator;
    }

    /**
     * @return \Iterator
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
     * @return int
     */
    public function replay()
    {
        $version = 0;
        /** @var Event $event */
        foreach ($this->streamIterator as $version => $event) {
            foreach ($this->anyEventCallbacks as $callback) {
                call_user_func($callback, $event);
            }
            if (!isset($this->eventCallbacks[$event->getType()])) {
                continue;
            }
            foreach ($this->eventCallbacks[$event->getType()] as $callback) {
                call_user_func($callback, $event);
            }
        }
        return $version;
    }

}