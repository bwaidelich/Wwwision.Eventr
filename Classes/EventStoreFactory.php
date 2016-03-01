<?php
namespace Wwwision\Eventr;

use EventStore\EventStore;
use TYPO3\Flow\Annotations as Flow;

class EventStoreFactory
{

    public function create($url)
    {
        return new EventStore($url);
    }
}