<?php
namespace Wwwision\Eventr\Adapters\Doctrine;

use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Utility\Now;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\Domain\Dto\WritableEvent;
use Wwwision\Eventr\Domain\Model\AggregateType;
use Wwwision\Eventr\EventStoreAdapterInterface;
use Wwwision\Eventr\EventStream;
use Wwwision\Eventr\ExpectedVersion;
use Wwwision\Eventr\WrongExpectedVersionException;

/**
 * Adapter for the doctrine based EventStore
 */
class EventStoreAdapter implements EventStoreAdapterInterface
{

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @Flow\Inject
     * @var Now
     */
    protected $now;

    /**
     * @param DoctrineObjectManager $doctrineEntityManager
     * @return void
     */
    public function injectDoctrineEntityManager(DoctrineObjectManager $doctrineEntityManager)
    {
        /** @var DoctrineEntityManager $doctrineEntityManager */
        $this->connection = $doctrineEntityManager->getConnection();
    }

    /**
     * {@inheritDoc}
     */
    public function writeToStream($streamName, WritableEvent $event, $expectedVersion = ExpectedVersion::ANY)
    {
        $this->connection->beginTransaction();

        $actualVersion = $this->connection->fetchColumn('SELECT MAX(version) FROM eventr_events WHERE stream = ?', [$streamName]);
        $newVersion = $actualVersion === NULL ? 0 : $actualVersion + 1;
        $this->verifyExpectedVersion($actualVersion, $expectedVersion);

        $eventData = [
            'stream' => $streamName,
            'version' => $newVersion,
            'saved_at' => $this->now->format('Y-m-d H:i:s'),
            'type' => $event->getType(),
            'data' => $event->hasData() ? json_encode($event->getData()) : null,
            'metadata' => $event->hasMetadata() ? json_encode($event->getMetadata()) : null,
        ];
        $this->connection->insert('eventr_events', $eventData);
        $this->connection->commit();

        return new Event($event->getType(), $eventData['version'], $event->getData(), $event->getMetadata());
    }

    /**
     * @param integer $actualVersion
     * @param integer $expectedVersion
     * @return void
     * @throws WrongExpectedVersionException
     */
    private function verifyExpectedVersion($actualVersion, $expectedVersion)
    {
        if ($expectedVersion === ExpectedVersion::ANY) {
            return;
        }
        if ($expectedVersion === ExpectedVersion::NO_STREAM && $actualVersion === null) {
            return;
        }
        if ($actualVersion !== null && $expectedVersion === (integer)$actualVersion ) {
            return;
        }
        throw new WrongExpectedVersionException(sprintf('Expected version: %d, actual version: %d', $expectedVersion, $actualVersion), 1457091612);
    }

    /**
     * {@inheritDoc}
     */
    public function getEventStreamFor(AggregateType $aggregateType, $offset = 0)
    {
        $query = 'SELECT * FROM eventr_events WHERE stream LIKE :stream';
        if ($offset > 0) {
            $query .= ' AND id >= :version';
        }
        #$query .= ' AND type = "addressAdded"';
        $query .= ' ORDER BY id';
        $streamNamePrefix = $aggregateType->getName() . '-%';
        $statement = $this->connection->prepare($query);
        $statement->bindParam(':stream', $streamNamePrefix);
        if ($offset > 0) {
            $version = $offset + 1;
            $statement->bindParam(':version', $version);
        }

        $streamIterator = new StreamIterator($statement);
        return new EventStream($streamIterator);
    }
}