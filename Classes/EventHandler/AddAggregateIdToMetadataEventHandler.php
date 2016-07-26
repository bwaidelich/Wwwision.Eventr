<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Dto\WritableEvent;

/**
 * @Flow\Scope("singleton")
 */
class AddAggregateIdToMetadataEventHandler implements EventHandlerInterface
{

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        if (!$event instanceof WritableEvent) {
            return;
        }
        if ($event->hasMetadataKey('id')) {
            return;
        }
        $event->addMetadata('id', $aggregate->getId());
    }

}