<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\Domain\Model\Projection;
use Wwwision\Eventr\Domain\Repository\AggregateTypeRepository;
use Wwwision\Eventr\Domain\Repository\ProjectionRepository;

/**
 * @Flow\Scope("singleton")
 */
class Eventr
{

    /**
     * @var AggregateTypeRepository
     */
    private $aggregateTypeRepository;


    /**
     * @var ProjectionRepository
     */
    private $projectionRepository;

    /**
     * @param AggregateTypeRepository $aggregateTypeRepository
     * @param ProjectionRepository $projectionRepository
     */
    public function __construct(AggregateTypeRepository $aggregateTypeRepository, ProjectionRepository $projectionRepository)
    {
        $this->aggregateTypeRepository = $aggregateTypeRepository;
        $this->projectionRepository = $projectionRepository;
    }

    /**
     * @param string $aggregateTypeName
     * @return AggregateType
     */
    public function registerAggregateType($aggregateTypeName)
    {
        if ($this->aggregateTypeRepository->findByIdentifier($aggregateTypeName) !== null) {
            throw new \InvalidArgumentException(sprintf('AggregateType "%s" is already registered!', $aggregateTypeName), 1456591609);
        }
        $aggregateType = new AggregateType($aggregateTypeName);
        $this->aggregateTypeRepository->add($aggregateType);
        return $aggregateType;
    }

    /**
     * @param string $aggregateName
     * @param AggregateId $id
     * @return Aggregate
     */
    public function getAggregate($aggregateName, AggregateId $id)
    {
        $aggregateType = $this->getAggregateType($aggregateName);
        return new Aggregate($aggregateType, $id);
    }
    /**
     * @param string $aggregateTypeName
     * @return AggregateType
     */
    public function getAggregateType($aggregateTypeName)
    {
        $aggregateType = $this->aggregateTypeRepository->findByIdentifier($aggregateTypeName);
        if ($aggregateType === null) {
            throw new \InvalidArgumentException(sprintf('AggregateType "%s" is not registered!', $aggregateTypeName), 1456502064);
        }
        return $aggregateType;
    }

    /**
     * @param Projection $projection
     * @return void
     */
    public function registerProjection(Projection $projection)
    {
        if ($this->projectionRepository->findByIdentifier($projection->getName()) !== null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is already registered!', $projection->getName()), 1456740995);
        }
        $this->projectionRepository->add($projection);
    }

    /**
     * @param string $projectionName
     * @return Projection
     */
    public function getProjection($projectionName)
    {
        return $this->projectionRepository->findByIdentifier($projectionName);
    }
}