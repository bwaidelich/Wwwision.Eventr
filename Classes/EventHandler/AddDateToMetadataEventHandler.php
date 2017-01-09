<?php
namespace Wwwision\Eventr\EventHandler;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Dto\WritableEvent;

/**
 * @Flow\Scope("singleton")
 */
class AddDateToMetadataEventHandler implements EventHandlerInterface
{

    /**
     * @Flow\Inject
     * @var Now
     */
    protected $now;

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
        if ($event->hasMetadataKey('date')) {
            return;
        }
        $event->addMetadata('date', $this->now->format(DATE_ISO8601));
    }

}