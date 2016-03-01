<?php
namespace Wwwision\Eventr;

use Doctrine\Common\Persistence\ObjectManager as EntityManager;
use Doctrine\DBAL\Connection;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\AggregateId;
use Wwwision\Eventr\Domain\Dto\Event;

class PdoProjectionAdapter implements ProjectionAdapterInterface
{
    /**
     * @var array
     */
    private $options;

    /**
     * @var string
     */
    private $tableName;

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
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function initializeObject()
    {
        $this->connection = $this->entityManager->getConnection();
        $this->tableName = $this->connection->quoteIdentifier($this->options['tableName']);
    }

    /**
     * {@inheritDoc}
     */
    public function project(AggregateId $aggregateId, array $state, Event $lastEvent)
    {
        $columnValues = array_merge(['id' => (string)$aggregateId, 'version' => $lastEvent->getVersion()], $state);
        $columns = '`' . implode('`, `', array_keys($columnValues)) . '`';
        $placeholders = ':' . implode(', :', array_keys($columnValues));
        $updates = [];
        foreach (array_keys($columnValues) as $columnName) {
            if ($columnName === 'id') {
                continue;
            }
            $updates[] = $columnName . ' = :' . $columnName;
        }
        $query = sprintf('INSERT INTO %s (%s) VALUES (%s) ON DUPLICATE KEY UPDATE %s', $this->tableName, $columns, $placeholders, implode(', ', $updates));

        $statement = $this->connection->prepare($query);
        $statement->execute($columnValues);
    }

    /**
     * {@inheritDoc}
     */
    public function findById(AggregateId $aggregateId)
    {
        return $this->connection->fetchAssoc(sprintf('SELECT * FROM %s WHERE id = ? LIMIT 1', $this->tableName), [$aggregateId]);
    }

    /**
     * {@inheritDoc}
     */
    public function findAll()
    {
        return $this->connection->fetchAll(sprintf('SELECT * FROM %s', $this->tableName));
    }
}