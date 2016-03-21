<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
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
     * @param string $id
     * @return Aggregate
     */
    public function getAggregate($aggregateName, $id)
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
        // HACK to persist registered events
        $this->aggregateTypeRepository->update($aggregateType);
        return $aggregateType;
    }

    /**
     * @return AggregateType[]
     */
    public function getAggregateTypes()
    {
        return $this->aggregateTypeRepository->findAll()->toArray();
    }

    /**
     * @param string $projectionName
     * @param AggregateType $aggregateType
     * @param array $mapping
     * @param array $adapterConfiguration
     * @return void
     */
    public function registerProjection($projectionName, AggregateType $aggregateType, array $mapping, array $adapterConfiguration)
    {
        if ($this->projectionRepository->findByIdentifier($projectionName) !== null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is already registered!', $projectionName), 1456740995);
        }
        $projection = new Projection($projectionName, $aggregateType, $mapping, $adapterConfiguration);
        $this->projectionRepository->add($projection);
    }

    /**
     * @param string $projectionName
     * @param array $newMapping
     * @param array $newAdapterConfiguration
     * @return void
     */
    public function updateProjection($projectionName, array $newMapping, array $newAdapterConfiguration = null)
    {
        /** @var Projection $projection */
        $projection = $this->projectionRepository->findByIdentifier($projectionName);
        if ($projection === null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is not registered!', $projection->getName()), 1457372107);
        }
        $projection->updateMapping($newMapping);
        if ($newAdapterConfiguration !== null) {
            $projection->updateAdapterConfiguration($newAdapterConfiguration);
        }
        $this->projectionRepository->update($projection);
    }

    /**
     * @param string $projectionName
     * @return Projection
     */
    public function getProjection($projectionName)
    {
        $projection = $this->projectionRepository->findByIdentifier($projectionName);
        if ($projection === null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is not registered!', $projectionName), 1457019162);
        }
        $this->projectionRepository->update($projection);
        return $projection;
    }

    /**
     * @return Projection[]
     */
    public function getProjections()
    {
        return $this->projectionRepository->findAll()->toArray();
    }
}