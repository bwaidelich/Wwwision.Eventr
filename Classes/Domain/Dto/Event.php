<?php
namespace Wwwision\Eventr\Domain\Dto;

use Neos\Flow\Annotations as Flow;

final class Event implements EventInterface
{
    /**
     * @var string
     */
    private $type;

    /**
     * @var integer
     */
    private $version;

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
     * @param integer $version
     * @param array $data
     * @param array $metadata
     */
    public function __construct($type, $version, array $data, array $metadata = null)
    {
        $this->type = $type;
        $this->version = (integer)$version;
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
     * @return integer
     */
    public function getVersion()
    {
        return $this->version;
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
     * @return array
     */
    public function getMetadata()
    {
        return $this->metadata;
    }
}
