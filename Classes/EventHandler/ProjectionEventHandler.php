<?php
namespace Wwwision\Eventr\EventHandler;

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Jobqueue\Common\Job\JobManager;
use TYPO3\Jobqueue\Common\Job\StaticMethodCallJob;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

/**
 * @Flow\Scope("singleton")
 */
class ProjectionEventHandler implements EventHandlerInterface
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
     * @param EventInterface $_
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $_)
    {
        // TODO only replay projections matching stream/event
        foreach ($this->projectionRepository->findSynchronousByAggregateType($aggregate->getType()) as $projection) {
            $projection->replay();
        }
        $this->jobManager->queue('eventr-projection', new StaticMethodCallJob(get_class($this), 'catchupProjections', [$aggregate->getType()]));
    }

    /**
     * @param AggregateType $aggregateType
     * @return void
     */
    public function catchupProjections(AggregateType $aggregateType)
    {
        foreach ($this->projectionRepository->findAsynchronousByAggregateType($aggregateType) as $projection) {
            $projection->replay();
        }
    }
}