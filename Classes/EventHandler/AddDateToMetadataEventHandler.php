<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Utility\Now;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

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
        if ($event->hasMetadataKey('date')) {
            return;
        }
        $event->addMetadata('date', $this->now->format(DATE_ISO8601));
    }

}