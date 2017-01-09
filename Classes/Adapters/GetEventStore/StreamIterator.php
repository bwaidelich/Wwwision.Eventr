<?php
namespace Wwwision\Eventr\Adapters\GetEventStore;

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Http\Client\RequestEngineInterface;
use Neos\Flow\Http\Request;
use Neos\Flow\Http\Uri;
use Wwwision\Eventr\Domain\Dto\Event;

/**
 * Iterator for geteventstore based EventStores
 */
class StreamIterator implements \Iterator
{

    /**
     * @Flow\Inject
     * @var RequestEngineInterface
     */
    protected $httpClient;

    /**
     * @var string
     */
    private $baseUrl;

    /**
     * @var string
     */
    private $streamName;

    /**
     * @var string
     */
    private $offset;

    /**
     * @var \Iterator
     */
    private $innerIterator;

    /**
     * @var string
     */
    private $last;

    /**
     * @var bool
     */
    private $continuous;

    /**
     * @param string $baseUrl
     * @param string $streamName
     * @param integer $offset
     * @param bool $continuous
     */
    public function __construct($baseUrl, $streamName, $offset = 0, $continuous = false)
    {
        $this->baseUrl = $baseUrl;
        $this->streamName = $streamName;
        $this->offset = $offset;
        $this->continuous = $continuous;
    }

    /**
     * @param string $head
     * @return string
     */
    private function getLast($head)
    {
        $atom = json_decode($this->fetch($head), true);
        $last = $this->getNamedLink($atom, 'last');
        if ($last !== null) {
            return $last;
        }
        return $this->getNamedLink($atom, 'self');
    }

    /**
     * @return string
     */
    private function readPrevious()
    {
        $atom = json_decode($this->fetch($this->last . '?embed=body'), true);
        $this->innerIterator = new \ArrayIterator(array_reverse($atom['entries']));
        $previous = $this->getNamedLink($atom, 'previous');
        if ($previous !== null) {
            return $previous;
        }
        return $this->last;
    }

    /**
     * @param string $url
     * @return string
     */
    private function fetch($url)
    {
        $request = Request::create(new Uri($url));
        $request->setHeader('Accept', 'application/vnd.eventstore.atom+json');
        if ($this->continuous) {
            $request->setHeader('ES-LongPoll', 15);
        }

        try {
            $response = $this->httpClient->sendRequest($request);
        } catch (\Exception $exception) {
            echo 'EXCEPTION: for url "' . $url . '": ' . $exception->getMessage();
            exit;
        }
        if ($response->getStatusCode() !== 200) {
            echo 'Unexpected status code ' . $response->getStatus() . ' for URL ' . $url;
            exit;
        }
        return $response->getContent();
    }

    /**
     * @param array $document
     * @param string $name
     * @return string
     */
    private function getNamedLink(array $document, $name)
    {
        foreach ($document['links'] as $link) {
            if ($link['relation'] !== $name) {
                continue;
            }
            return $link['uri'];
        }
        return null;
    }

    /**
     * @return Event
     */
    public function current()
    {
        $entry = $this->innerIterator->current();
        $data = json_decode($entry['data'], true);
        if ($data === null) {
            $data = [];
        }
        $metadata = $entry['isMetaData'] ? json_decode($entry['metaData'], true) : null;
        return new Event($entry['eventType'], $entry['eventNumber'], $data, $metadata);
    }

    /**
     * @return void
     */
    public function next()
    {
        $this->innerIterator->next();
        if ($this->innerIterator->valid()) {
            return;
        }

        do {
            $current = $this->readPrevious();
            if ($current !== $this->last) {
                $this->last = $current;
                return;
            }
            if (!$this->continuous) {
                return;
            }
            echo '---' . PHP_EOL;
            sleep(2);
        } while (true);
    }

    /**
     * @return string
     */
    public function key()
    {
        $entry = $this->innerIterator->current();
        return $entry['positionEventNumber'];
    }

    /**
     * @return bool
     */
    public function valid()
    {
        return $this->innerIterator->valid();
    }

    /**
     * @return void
     */
    public function rewind()
    {
        if ($this->offset > 0) {
            $this->last = sprintf('%s/streams/%s/%d/forward/20', $this->baseUrl, $this->streamName, $this->offset);
        } else {
            $this->last = $this->getLast(sprintf('%s/streams/%s', $this->baseUrl, $this->streamName));
        }
        $this->last = $this->readPrevious();
    }
}