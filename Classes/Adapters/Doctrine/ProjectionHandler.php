<?php
namespace Wwwision\Eventr\Adapters\Doctrine;

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DBALException;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use TYPO3\Eel\CompilingEvaluator as EelEvaluator;
use TYPO3\Eel\Context as EelContext;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Utility\ObjectAccess;
use Neos\Flow\Reflection\ReflectionService;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\Domain\Dto\EventInterface;
use Wwwision\Eventr\ProjectionHandlerInterface;

/**
 * Projection handler for doctrine based projections
 */
class ProjectionHandler implements ProjectionHandlerInterface
{
    /**
     * @Flow\Inject
     * @var DoctrineEntityManager;
     */
    protected $doctrineEntityManager;

    /**
     * @Flow\Inject
     * @var ReflectionService
     */
    protected $reflectionService;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var EelEvaluator
     */
    protected $eelEvaluator;

    /**
     * @var string
     */
    private $readModelClassName;

    /**
     * @var array
     */
    private $mapping;

    /**
     * @var array
     */
    private $eelHelper = [];

    /**
     * @var ClassMetadata
     */
    private $readModelMetadata;

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->readModelClassName = $options['readModelClassName'];
        $this->mapping = $options['mapping'];
        if (isset($options['eelHelper'])) {
            $this->eelHelper = $options['eelHelper'];
        }
    }

    /**
     * @param DoctrineObjectManager $doctrineEntityManager
     * @return void
     */
    public function injectDoctrineEntityManager(DoctrineObjectManager $doctrineEntityManager)
    {
        $this->doctrineEntityManager = $doctrineEntityManager;
    }

    public function initializeObject()
    {
        $this->connection = $this->doctrineEntityManager->getConnection();
        $this->readModelMetadata = $this->doctrineEntityManager->getClassMetadata($this->readModelClassName);
    }

    /**
     * @param Aggregate $aggregate
     * @param EventInterface $event
     * @return void
     */
    public function handle(Aggregate $aggregate, EventInterface $event)
    {
        $eventType = (string)$event->getType();
        if (!isset($this->mapping[$eventType])) {
            return;
        }
        $eelContext = new EelContext(['event' => $event]);
        foreach ($this->eelHelper as $helperName => $helperClassName) {
            $eelContext->push(new $helperClassName(), $helperName);
        }
        $eventMapping = $this->mapping[$eventType];
        if (isset($eventMapping['id'])) {
            $readModelId = $this->eelEvaluator->evaluate($eventMapping['id'], $eelContext);
        } else {
            $readModelId = (string)$event->getMetadata()['id'];
        }
        $state = (array)$this->getStateFor($readModelId);
        // TODO initial state (maybe defer to adapter: "state.foo + 1" => "`table.foo` + 1"

        foreach ($eventMapping as $propertyName => $expression) {
            $state[$propertyName] = $this->map($eelContext, $state, $expression);
        }
        if ($event instanceof Event) {
            $this->project($state, $event);
        }
    }

    /**
     * @param array $state
     * @param Event $event
     * @throws \Exception
     */
    public function project($state, Event $event)
    {
        $mapping = [];
        foreach ($state as $key => $value) {
            if (is_array($value)) {
                if ($this->readModelMetadata->hasAssociation($key)) {
                    $className = $this->readModelMetadata->getAssociationTargetClass($key);
                    if (!isset($mapping[$className])) {
                        $mapping[$className] = [];
                    }
                    $mapping[$className] = array_merge($mapping[$className], $value);
                } else {
                    $mapping[$this->readModelClassName][$key] = json_encode($value);
                }
                continue;
            }
            if ($this->readModelMetadata->hasField($key) || $this->readModelMetadata->isSingleValuedAssociation($key)) {
                $mapping[$this->readModelClassName][$key] = $value;
                continue;
            }
            throw new \Exception(sprintf('No field/association "%s" in read model "%s"', $key, $this->readModelClassName), 1457352291);
        }
        /** @noinspection PhpUndefinedFieldInspection */
        if ($this->readModelMetadata->isVersioned) {
            /** @noinspection PhpUndefinedFieldInspection */
            $mapping[$this->readModelClassName][$this->readModelMetadata->versionField] = $event->getVersion();
        }
        foreach ($mapping as $className => $columnValues) {
            $classMetadata = $this->doctrineEntityManager->getClassMetadata($className);
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
            $this->persistenceManager->clearState();
        }
    }

    /**
     * @param EelContext $eelContext
     * @param array $state
     * @param string $expression
     * @return array|mixed
     */
    private function map($eelContext, array $state, $expression)
    {
        if (is_array($expression)) {
            $subState = [];
            foreach ($expression as $subPropertyName => $subExpression) {
                $subState[$subPropertyName] = $this->map($eelContext, $state, $subExpression);
            }
            return $subState;
        }
        $eelContext->push($state, 'state');
        return $this->eelEvaluator->evaluate($expression, $eelContext);
    }
    /**
     * {@inheritDoc}
     */
    public function removeAll()
    {
        $query = sprintf('DELETE FROM %s', $this->readModelMetadata->getTableName());
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $this->persistenceManager->clearState();
    }

    /**
     * {@inheritDoc}
     */
    public function getStateFor($id)
    {
        $entity = $this->persistenceManager->getObjectByIdentifier($id, $this->readModelClassName);
        if ($entity === null) {
            return null;
        }
        return ObjectAccess::getGettableProperties($entity);
    }

    /**
     * {@inheritDoc}
     */
    public function resetState()
    {
        $query = sprintf('DELETE FROM %s', $this->readModelMetadata->getTableName());
        $statement = $this->connection->prepare($query);
        $statement->execute();
        $this->persistenceManager->clearState();
    }
}