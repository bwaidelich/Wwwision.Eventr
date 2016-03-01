<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Dto\Event;

interface ProjectionAdapterInterface
{

//    public function __construct(array $options);

    /**
     * @param AggregateId $aggregateId
     * @param array $state
     * @param Event $lastEvent
     * @return void
     */
    public function project(AggregateId $aggregateId, array $state, Event $lastEvent);

    /**
     * @param AggregateId $aggregateId
     * @return array
     */
    public function findById(AggregateId $aggregateId);

    /**
     * @return array
     */
    public function findAll();
}