<?php
namespace Wwwision\Eventr\Domain\Dto;

use TYPO3\Flow\Annotations as Flow;

final class WritableEvent implements EventInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var array
     */
    private $data;

    /**
     * @var array
     */
    private $metadata;

    /**
     * @param string $type
     * @param array $data
     * @param array $metadata
     */
    public function __construct($type, array $data, array $metadata = null)
    {
        $this->type = $type;
        $this->data = $data;
        $this->metadata = $metadata;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function hasData()
    {
        return $this->data !== null;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function hasMetadata()
    {
        return $this->metadata !== null;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function hasMetadataKey($key)
    {
        return $this->hasMetadata() && isset($this->metadata[$key]);
    }

    /**
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addMetadata($key, $value)
    {
        if (!$this->hasMetadata()) {
            $this->metadata = [];
        }
        $this->metadata[$key] = $value;
    }
}
