<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\EventInterface;

interface ProjectionAdapterInterface
{

//    public function __construct(array $options);

    /**
     * @param string $aggregateId
     * @param mixed $state
     * @param EventInterface $event
     * @return void
     */
    public function project($aggregateId, $state, EventInterface $event);

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