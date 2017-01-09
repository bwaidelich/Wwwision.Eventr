<?php
namespace Wwwision\Eventr;

use Neos\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\ProjectionConfiguration;
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
        return $this->getAggregateType($aggregateName)->getAggregate($id);
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
     * @param ProjectionConfiguration $configuration
     * @return Projection
     */
    public function registerProjection($projectionName, ProjectionConfiguration $configuration)
    {
        if ($this->projectionRepository->findByIdentifier($projectionName) !== null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is already registered!', $projectionName), 1456740995);
        }
        $projection = new Projection($projectionName, $configuration);
        $this->projectionRepository->add($projection);
    }

    /**
     * @param string $projectionName
     * @param ProjectionConfiguration $newConfiguration
     * @return void
     */
    public function updateProjection($projectionName, ProjectionConfiguration $newConfiguration)
    {
        /** @var Projection $projection */
        $projection = $this->projectionRepository->findByIdentifier($projectionName);
        if ($projection === null) {
            throw new \InvalidArgumentException(sprintf('Projection "%s" is not registered!', $projectionName), 1457372107);
        }
        if ($newConfiguration->aggregateType !== $projection->getAggregateType()) {
            throw new \InvalidArgumentException('The AggregateType of an existing projection can\'t be changed!', 1464094098);
        }

        if ($newConfiguration->handlerClassName !== $projection->getHandlerClassName()) {
            $projection->updateHandlerClassName($newConfiguration->handlerClassName);
        }
        if ($newConfiguration->handlerOptions !== $projection->getHandlerOptions()) {
            $projection->updateHandlerOptions($newConfiguration->handlerOptions);
        }
        if ($newConfiguration->batchSize !== $projection->getBatchSize()) {
            $projection->updateBatchSize($newConfiguration->batchSize);
        }
        $this->projectionRepository->update($projection);
    }

    /**
     * @param string $projectionName
     * @return bool
     */
    public function hasProjection($projectionName)
    {
        return $this->projectionRepository->findByIdentifier($projectionName) !== null;
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