<?php
namespace Wwwision\Eventr\Domain\Dto;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Model\AggregateType;

final class ProjectionConfiguration
{
    /**
     * @var AggregateType
     */
    public $aggregateType;

    /**
     * @var string
     */
    public $handlerClassName;

    /**
     * @var array
     */
    public $handlerOptions = array();

    /**
     * @var bool
     */
    public $synchronous = false;

    /**
     * @param AggregateType $aggregateType
     * @param string $handlerClassName
     */
    public function __construct(AggregateType $aggregateType, $handlerClassName)
    {
        $this->aggregateType = $aggregateType;
        $this->handlerClassName = $handlerClassName;
    }

}
