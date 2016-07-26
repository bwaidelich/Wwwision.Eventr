<?php
namespace Wwwision\Eventr\EventHandler;

use Flowpack\JobQueue\Common\Job\JobManager;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

/**
 * @Flow\Scope("singleton")
 */
class AsynchronousProjectionEventHandler implements EventHandlerInterface
{
    /**
     * @Flow\Inject
     * @var ProjectionRepository
     */
    protected $projectionRepository;

    /**
     * @Flow\Inject
     * @var JobManager
     */
    protected $jobManager;

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        // TODO only apply to projections matching stream/event?
        foreach ($this->projectionRepository->findAsynchronousByAggregateType($aggregate->getType()) as $projection) {
            $this->jobManager->queue('eventr-projection', new ProjectionHandleJob($projection->getName(), $aggregate->getType()->getName(), $aggregate->getId(), $event));
        }
    }

}