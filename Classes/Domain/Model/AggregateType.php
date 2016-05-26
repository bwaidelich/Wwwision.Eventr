<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\PersistenceManagerInterface;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\EventStore;
use Wwwision\Eventr\EventStream;

/**
 * @Flow\Entity
 * @ORM\Table(name="eventr_aggregate_types")
 */
class AggregateType
{

    /**
     * HACK
     *
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

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
     * @Flow\Inject
     * @var EventStore
     */
    protected $eventStore;

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
     * @return EventType
     */
    public function registerEventType($eventName, array $eventSchema = null)
    {
        if ($this->hasEventType($eventName)) {
            throw new \InvalidArgumentException(sprintf('EventType "%s" is already registered for AggregateType "%s"', $eventName, $this->name), 1456499935);
        }
        $eventType = new EventType($this, $eventName, $eventSchema);
        $this->eventTypes->add($eventType);
        return $eventType;
    }

    /**
     * @param string $eventName
     * @param array $eventSchema
     * @return void
     */
    public function updateEventType($eventName, array $eventSchema = null)
    {
        if (!$this->hasEventType($eventName)) {
            throw new \InvalidArgumentException(sprintf('EventType "%s" is not registered for AggregateType "%s"', $eventName, $this->name), 1457006624);
        }
        // HACK
        $eventType = $this->getEventType($eventName);
        $eventType->updateSchema($eventSchema);
        $this->persistenceManager->update($eventType);
    }

    /**
     * @param string $eventName
     * @return void
     */
    public function removeEventType($eventName)
    {
        if (!$this->hasEventType($eventName)) {
            throw new \InvalidArgumentException(sprintf('EventType "%s" is not registered for AggregateType "%s"', $eventName, $this->name), 1457005725);
        }
        // HACK
        $this->eventTypes->removeElement($this->getEventType($eventName));
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

    /**
     * @return EventType[]
     */
    public function getEventTypes()
    {
        return $this->eventTypes->toArray();
    }

    /**
     * @param int $offset
     * @return EventStream
     */
    public function getEventStream($offset = 0)
    {
        return $this->eventStore->getEventStreamFor($this, $offset);
    }

    /**
     * @param string $id
     * @return Aggregate
     */
    public function getAggregate($id)
    {
        return new Aggregate($this, $id);
    }

    /**
     * @return string
     */
    function __toString() {
        return $this->name;
    }

}