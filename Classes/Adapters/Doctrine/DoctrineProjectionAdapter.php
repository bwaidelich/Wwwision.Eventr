<?php
namespace Wwwision\Eventr\Adapters\Doctrine;

use Doctrine\Common\Persistence\ObjectManager as EntityManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Persistence\RepositoryInterface;
use TYPO3\Flow\Reflection\ObjectAccess;
use TYPO3\Flow\Reflection\ReflectionService;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\ProjectionAdapterInterface;

class DoctrineProjectionAdapter implements ProjectionAdapterInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $readModelClassName;

    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @Flow\Inject
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;


    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function initializeObject()
    {
        $this->connection = $this->entityManager->getConnection();
        $this->readModelClassName = $this->options['readModelClassName'];
        $repositoryClassName = $this->reflectionService->getClassSchema($this->readModelClassName)->getRepositoryClassName();
        $this->repository = new $repositoryClassName();
    }

    /**
     * {@inheritDoc}
     */
    public function project($aggregateId, $state, EventInterface $event)
    {
        $readModelMetadata = $this->entityManager->getClassMetadata($this->readModelClassName);
        $mapping = [];
        $this->connection->beginTransaction();
        foreach ($state as $key => $value) {
            if (is_array($value)) {
                if ($readModelMetadata->hasAssociation($key)) {
                    $className = $readModelMetadata->getAssociationTargetClass($key);
                    if (!isset($mapping[$className])) {
                        $mapping[$className] = [];
                    }
                    $mapping[$className] = array_merge($mapping[$className], $value);
                } else {
                    $mapping[$this->readModelClassName][$key] = json_encode($value);
                }
                continue;
            }
            if ($readModelMetadata->hasField($key) || $readModelMetadata->isSingleValuedAssociation($key)) {
                $mapping[$this->readModelClassName][$key] = $value;
                continue;
            }
            throw new \Exception(sprintf('No field/association "%s" in read model "%s"', $key, $this->readModelClassName), 1457352291);
        }
        /** @noinspection PhpUndefinedFieldInspection */
        if ($readModelMetadata->isVersioned) {
            /** @noinspection PhpUndefinedFieldInspection */
            $mapping[$this->readModelClassName][$readModelMetadata->versionField] = $event->getVersion();
        }
        foreach ($mapping as $className => $columnValues) {
            $classMetadata = $this->entityManager->getClassMetadata($className);
            foreach ($classMetadata->getIdentifierFieldNames() as $idFieldName) {
                if (isset($columnValues[$idFieldName])) {
                    continue;
                }
                $columnValues[$idFieldName] = $event->getMetadata()[$idFieldName];
            }

            $columns = '`' . implode('`, `', array_keys($columnValues)) . '`';
            $placeholders = ':' . implode(', :', array_keys($columnValues));
            $updates = [];
            foreach (array_keys($columnValues) as $columnName) {
                if (in_array($columnName, $classMetadata->getIdentifierFieldNames())) {
                    continue;
                }
                $updates[] = $columnName . ' = :' . $columnName;
            }
            $query = sprintf('INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s', $classMetadata->getTableName(), $columns, $placeholders, implode(', ', $updates));
            $statement = $this->connection->prepare($query);
            try {
                $statement->execute($columnValues);
            } catch (DBALException $exception) {
                echo 'EXCEPTION: ' . $exception->getMessage() . PHP_EOL;
            }
        }
        $this->connection->commit();
    }

    /**
     * {@inheritDoc}
     */
    public function findById($aggregateId)
    {
        $entity = $this->repository->findByIdentifier($aggregateId);
        if ($entity === null) {
            return null;
        }
        return ObjectAccess::getGettableProperties($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return $this->repository->findAll();
    }
}