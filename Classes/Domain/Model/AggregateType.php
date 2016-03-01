<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use TYPO3\Flow\Annotations as Flow;
use Doctrine\ORM\Mapping as ORM;

/**
 * @Flow\Entity
 */
class AggregateType
{
    /**
     * @ORM\Id
     * @var string
     */
    protected $name;

    /**
     * @ORM\OneToMany(mappedBy="aggregateType", cascade={"persist","remove"}, orphanRemoval=true)
     * @var ArrayCollection<EventType>
     */
    protected $eventTypes;

    /**
     * @param string $name
     */
    public function __construct($name)
    {
        $this->name = $name;
        $this->eventTypes = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $eventName
     * @param array $eventSchema
     * @return void
     */
    public function registerEventType($eventName, array $eventSchema = null)
    {
        if ($this->hasEventType($eventName)) {
            throw new \InvalidArgumentException(sprintf('EventType "%s" is already registered for AggregateType "%s"', $eventName, $this->name), 1456499935);
        }
        $eventType = new EventType($this, $eventName, $eventSchema);
        $this->eventTypes->add($eventType);
    }

    public function hasEventType($eventName)
    {
        /** @noinspection PhpUnusedParameterInspection */
        return $this->eventTypes->exists(function($index, EventType $eventType) use ($eventName) { return $eventType->getName() === $eventName; });
    }

    /**
     * @param string $eventName
     * @return EventType
     */
    public function getEventType($eventName)
    {
        /** @var EventType $eventType */
        foreach ($this->eventTypes as $eventType) {
            if ($eventType->getName() === $eventName) {
                return $eventType;
            }
        }
        throw new \InvalidArgumentException(sprintf('EventType "%s" is not registered for AggregateType "%s"', $eventName, $this->name), 1456499945);
    }

}