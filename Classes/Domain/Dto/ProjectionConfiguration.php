<?php
namespace Wwwision\Eventr\Domain\Dto;

use Neos\Flow\Annotations as Flow;
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
     * @var integer
     */
    public $batchSize = 0;

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
