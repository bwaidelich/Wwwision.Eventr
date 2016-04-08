<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;

interface ProjectionAdapterInterface
{

//    public function __construct(array $options);

    /**
     * @param string $aggregateId
     * @param mixed $state
     * @param Event $event
     * @return void
     */
    public function project($aggregateId, $state, Event $event);

    /**
     * @param string $aggregateId
     * @return array
     */
    public function findById($aggregateId);

    /**
     * @return array
     */
    public function findAll();
}