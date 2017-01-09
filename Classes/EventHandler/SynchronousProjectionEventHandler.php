<?php
namespace Wwwision\Eventr\EventHandler;

use Neos\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

/**
 * @Flow\Scope("singleton")
 */
class SynchronousProjectionEventHandler implements EventHandlerInterface
{
    /**
     * @Flow\Inject
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        // TODO only apply to projections matching stream/event?
        foreach ($this->projectionRepository->findSynchronousByAggregateType($aggregate->getType()) as $projection) {
            $projection->handle($aggregate, $event);
        }
    }

}