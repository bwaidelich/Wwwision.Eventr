<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;

/**
 * @Flow\Entity
 * @ORM\Table(name="eventr_event_types")
 */
class EventType
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(inversedBy="eventTypes")
     * @var AggregateType
     */
    protected $aggregateType;

    /**
     * @ORM\Id
     * @var string
     */
    protected $name;

    /**
     * @ORM\Column(name="`schema`", nullable=true, type="json_array")
     * @var array
     */
    protected $schema = null;

    /**
     * @param AggregateType $aggregateType
     * @param string $name
     * @param array $schema
     */
    public function __construct(AggregateType $aggregateType, $name, array $schema = null)
    {
        $this->aggregateType = $aggregateType;
        $this->name = $name;
        $this->schema = $schema;
    }

    /**
     * @return AggregateType
     */
    public function getAggregateType()
    {
        return $this->aggregateType;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * HACK HACK HACK
     * @param array $schema
     * @return void
     */
    public function updateSchema(array $schema = null) {
        $this->schema = $schema;
    }

    /**
     * @return bool
     */
    public function hasSchema()
    {
        // TODO The 2nd part is needed because Doctrine hydrates NULL to an empty array
        return $this->schema !== null && $this->schema !== [];
    }

    /**
     * @return string
     */
    function __toString()
    {
        return sprintf('%s.%s', $this->aggregateType->getName(), $this->name);
    }


}