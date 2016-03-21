<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\Domain\Dto\EventInterface;

interface EventPublisherInterface
{
    public function publish(EventInterface $event);
}