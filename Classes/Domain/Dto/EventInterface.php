<?php
namespace Wwwision\Eventr\Domain\Dto;

use Neos\Flow\Annotations as Flow;

interface EventInterface
{

    /**
     * @return string
     */
    public function getType();

    /**
     * @return bool
     */
    public function hasData();

    /**
     * @return array
     */
    public function getData();

    /**
     * @return bool
     */
    public function hasMetadata();

    /**
     * @return array
     */
    public function getMetadata();
}
