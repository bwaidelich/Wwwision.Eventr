<?php
namespace Wwwision\Eventr\Adapters\Doctrine;

use Doctrine\DBAL\Driver\Statement;
use Wwwision\Eventr\Domain\Dto\Event;

/**
 * Iterator for the doctrine based EventStore
 */
final class StreamIterator implements \Iterator
{

    /**
     * @var Statement
     */
    private $statement;

    /**
     * @var array|false
     */
    private $currentEventData;

    /**
     * @var int
     */
    private $currentId;

    /**
     * @param Statement $statement
     */
    public function __construct(Statement $statement) {
        $this->statement = $statement;

        $this->rewind();
    }

    /**
     * @return null|Event
     */
    public function current()
    {
        if ($this->currentEventData === false) {
            return null;
        }
        $data = json_decode($this->currentEventData['data'], true);
        $metadata = isset($this->currentEventData['metadata']) ? json_decode($this->currentEventData['metadata'], true) : null;
        return new Event($this->currentEventData['type'], (integer)$this->currentEventData['version'], $data, $metadata);
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->currentEventData = $this->statement->fetch();

        if ($this->currentEventData !== false) {
            $this->currentId = (integer)$this->currentEventData['id'];
        } else {
            $this->currentId = -1;
        }
    }

    /**
     * @return bool|int
     */
    public function key()
    {
        if ($this->currentId === -1) {
            return false;
        }

        return $this->currentId;
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->currentEventData !== false;
    }

    /**
     * @return void
     */
    public function rewind()
    {
        //Only perform rewind if current item is not the first element
        if ($this->currentId === 0) {
            return;
        }
        $this->statement->execute();

        $this->currentEventData = null;
        $this->currentId = -1;

        $this->next();
    }
}