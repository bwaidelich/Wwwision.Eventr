<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\Event;
use Wwwision\Eventr\EventHandler\EventHandlerInterface;

/**
 * This interface can be added to a {@link ProjectionHandlerInterface}, so that the projection
 * handler can determine where the aggregate ID comes from
 */
interface GetAggregateIdFromEventInterface
{

    /**
     * @param Event $event
     * @return string the ID of the event
     */
    public function getAggregateIdFromEvent(Event $event);
}
