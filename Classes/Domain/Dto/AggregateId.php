<?php
namespace Wwwision\Eventr\Domain\Dto;

use Doctrine\ORM\Mapping as ORM;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Algorithms;

class AggregateId
{
    /**
     * @var string
     */
    private $id;

    /**
     * @param string $id
     */
    public function __construct($id)
    {
        $this->id = $id;
    }

    /**
     * @return static
     */
    static public function create()
    {
        return new static(Algorithms::generateUUID());
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }


}