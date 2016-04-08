<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;

interface EventHandlerInterface
{
    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event);

}