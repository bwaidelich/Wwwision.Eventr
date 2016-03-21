<?php
namespace Wwwision\Eventr\Adapters\EventStore;

use EventStore\EventStore as EventStoreClient;
use EventStore\StreamFeed\EntryEmbedMode;
use EventStore\StreamFeed\LinkRelation;
use EventStore\StreamFeed\StreamFeed;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\EventStreamIteratorInterface;

final class EventStoreStreamIterator implements EventStreamIteratorInterface
{
    /**
     * @var EventStoreClient;
     */
    private $eventStore;

    /**
     * @var string
     */
    private $streamName;

    /**
     * @var bool
     */
    private $rewinded = false;

    /**
     * @var integer
     */
    private $offset;

    /**
     * @var \ArrayIterator
     */
    private $innerIterator;

    /**
     * @var StreamFeed
     */
    private $feed;

    /**
     * @param EventStoreClient $eventStore
     * @param string $streamName
     * @param int $offset
     */
    public function __construct(EventStoreClient $eventStore, $streamName, $offset = 0) {
        $this->eventStore = $eventStore;
        $this->streamName = $streamName;
        $this->offset = $offset;
    }

    public function current()
    {
        return $this->innerIterator->current();
    }

    public function next()
    {
        $this->rewinded = false;
        $this->innerIterator->next();

        if (!$this->innerIterator->valid()) {
            $this->feed = $this
                ->eventStore
                ->navigateStreamFeed(
                    $this->feed,
                    LinkRelation::PREVIOUS()
                )
            ;

            $this->createInnerIterator();
        }
    }

    public function key()
    {
        // FIXME this should be the unique key of the current event in this stream
        return $this->innerIterator->key();
    }

    public function valid()
    {
        return $this->innerIterator->valid();
    }

    public function rewind()
    {
        if ($this->rewinded) {
            return;
        }

        if ($this->offset > 0) {
            $startingUrl = sprintf('%s/streams/%s/%d/backward/2', $this->eventStore->getUrl(), $this->streamName, $this->offset);
            $this->feed = $this->eventStore->readStreamFeed($startingUrl, EntryEmbedMode::BODY());
        } else {
            $this->feed = $this->eventStore->openStreamFeed($this->streamName, EntryEmbedMode::BODY());
            if ($this->feed->hasLink(LinkRelation::LAST())) {
                $this->feed = $this
                    ->eventStore
                    ->navigateStreamFeed(
                        $this->feed,
                        LinkRelation::LAST()
                    );
            }
        }

        $this->createInnerIterator();

        $this->rewinded = true;
    }

    private function createInnerIterator()
    {
        if ($this->feed !== null) {
            $entries = $this->feed->getJson();
        } else {
            $entries = [];
        }

        if (empty($entries)) {
            $this->innerIterator = new \ArrayIterator([]);

            return;
        }

        $this->innerIterator = new \ArrayIterator(
            array_map(
                function (array $entry) {
                    $eventData = json_decode($entry['data'], true);
                    $eventMetadata = !empty($entry['metaData']) ? json_decode($entry['metaData'], true) : null;
                    return new Event($entry['eventType'], $entry['positionEventNumber'], $eventData, $eventMetadata);
                },
                $entries['entries']
            )
        );
    }
}