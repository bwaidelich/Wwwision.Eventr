<?php
namespace Wwwision\Eventr\Adapters\EventStore;

use EventStore\EventStore as EventStoreClient;
use TYPO3\Flow\Annotations as Flow;

class EventStoreFactory
{

    public function create($url)
    {
        return new EventStoreClient($url);
    }
}