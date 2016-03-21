<?php
namespace Wwwision\Eventr\Domain\Model;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Error;
use TYPO3\Flow\Utility\SchemaValidator;

/**
 * @Flow\Entity
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
     * @Flow\Inject
     * @var SchemaValidator
     */
    protected $schemaValidator;

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
     * @param array $payload
     * @return void
     */
    public function validatePayload(array $payload = [])
    {
        if (!$this->hasSchema()) {
            if ($payload !== []) {
                throw new \InvalidArgumentException(sprintf('No payload is expected for EventType "%s"', $this), 1456505193);
            }
            return;
        }
        $schema = ['type' => 'dictionary', 'additionalProperties' => false, 'properties' => $this->schema];
        $result = $this->schemaValidator->validate($payload, $schema);
        if (!$result->hasErrors()) {
            return;
        }
        $message = '';
        foreach ($result->getFlattenedErrors() as $propertyPath => $errors) {
            $message .= sprintf('"%s":' . chr(10), $propertyPath);
            /** @var Error $error */
            foreach ($errors as $error) {
                $message .= ' ' . $error->render() . chr(10);
            }
        }
        throw new \InvalidArgumentException(sprintf('Payload does not adhere to schema of EventType "%s"%s%s', $this, chr(10), $message), 1456504367);
    }

    /**
     * @return string
     */
    function __toString()
    {
        return sprintf('%s.%s', $this->aggregateType->getName(), $this->name);
    }


}