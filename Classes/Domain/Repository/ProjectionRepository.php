<?php
namespace Wwwision\Eventr\Domain\Repository;

use Doctrine\ORM\Mapping as ORM;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Repository;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\Domain\Model\Projection;

/**
 * @Flow\Scope("singleton")
 */
class ProjectionRepository extends Repository
{

    /**
     * @param AggregateType $aggregateType
     * @return Projection[]
     */
    public function findSynchronousByAggregateType(AggregateType $aggregateType)
    {
        $query = $this->createQuery();
        return
            $query->matching(
                $query->logicalAnd([
                    $query->equals('aggregateType', $aggregateType),
                    $query->equals('synchronous', true)
                ])
            )
            ->execute()
            ->toArray();
    }

    /**
     * @param AggregateType $aggregateType
     * @return Projection[]
     */
    public function findAsynchronousByAggregateType(AggregateType $aggregateType)
    {
        $query = $this->createQuery();
        return
            $query->matching(
                $query->logicalAnd([
                    $query->equals('aggregateType', $aggregateType),
                    $query->equals('synchronous', false)
                ])
            )
            ->execute()
            ->toArray();
    }

}