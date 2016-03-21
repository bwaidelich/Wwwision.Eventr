<?php
namespace Wwwision\Eventr;

use GuzzleHttp\Client as GuzzleClient;
use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;

/**
 */
class EventStream
{
    /**
     * @var array
     */
    private $eventCallbacks = ['$any' => []];

    /**
     * @var string
     */
    private $name;

    /**
     * @var GuzzleClient
     */
    private $client;

    /**
     * @param string $streamName
     * @param string $baseUri
     */
    public function __construct($streamName, $baseUri)
    {
        $this->name = $streamName;
        $this->client = new GuzzleClient(['base_uri' => $baseUri]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param int $offset
     * @return void
     */
    public function listen($offset = 0)
    {
        $last = null;
        if ($offset > 0) {
            $last = sprintf('/streams/%s/%d/forward/20', $this->name, $offset + 1);
        }
        while ($last === null) {
            $last = $this->getLast(sprintf('/streams/%s', $this->name));
            if ($last === null) {
                sleep(2);
            }
        }
        do {
            $current = $this->readPrevious($last);
            if ($last === $current) {
                echo '---' . PHP_EOL;
                sleep(2);
            }
            $last = $current;
        } while (true);
    }

    /**
     * @param \Closure $onEventCallback
     * @return void
     */
    public function onAny(\Closure $onEventCallback)
    {
        $this->eventCallbacks['$any'][] = $onEventCallback;
    }

    /**
     * @param string $eventName
     * @param \Closure $onEventCallback
     * @return void
     */
    public function on($eventName, \Closure $onEventCallback)
    {
        if (!isset($this->eventCallbacks[$eventName])) {
            $this->eventCallbacks[$eventName] = [];
        }
        $this->eventCallbacks[$eventName][] = $onEventCallback;
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
     * @param array $entry
     * @return void
     */
    private function processEntry(array $entry)
    {
        $event = new Event($entry['eventType'], $entry['eventNumber'], json_decode($entry['data'], true), json_decode($entry['metaData'], true));
        foreach ($this->eventCallbacks['$any'] as $callback) {
            call_user_func($callback, $event);
        }
        if (isset($this->eventCallbacks[$event->getType()])) {
            foreach ($this->eventCallbacks[$event->getType()] as $callback) {
                call_user_func($callback, $event);
            }
        }
    }

    /**
     * @param string $url
     * @return string
     */
    private function readPrevious($url)
    {
        $atom = json_decode($this->fetch($url . '?embed=body'), true);
        foreach (array_reverse($atom['entries']) as $entry) {
            $this->processEntry($entry);
        }
        $previous = $this->getNamedLink($atom, 'previous');
        if ($previous !== null) {
            return $previous;
        }
        return $url;
    }

    /**
     * @param string $url
     * @return string
     */
    private function fetch($url)
    {
        try {
            $response = $this->client->request('get', $url, [
                'headers' => [
                    'Accept' => 'application/vnd.eventstore.atom+json',
//                    'ES-LongPoll' => 15
                ]
//                'auth' => ['admin', 'changeit'],
//                'headers' => [
//                ],
            ]);
        } catch (\Exception $exception) {
            echo 'EXCEPTION: for url "' . $url . '": ' . $exception->getMessage();
            exit;
        }
        return (string)$response->getBody();
    }

    /**
     * @param array $document
     * @param string $name
     * @return string
     */
    private function getNamedLink($document, $name)
    {
        foreach ($document['links'] as $link) {
            if ($link['relation'] !== $name) {
                continue;
            }
            return $link['uri'];
        }
        return null;
    }
}