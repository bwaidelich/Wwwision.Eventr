<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Core\Bootstrap;
use TYPO3\Flow\Package\Package as BasePackage;
use Wwwision\Eventr\Domain\Dto\Aggregate;
use Wwwision\Eventr\EventHandler\SynchronousProjectionEventHandler;

/**
 * The Eventr Package
 */
class Package extends BasePackage
{

    /**
     * @param Bootstrap $bootstrap
     * @return void
     */
    public function boot(Bootstrap $bootstrap)
    {
        $bootstrap->getSignalSlotDispatcher()->connect(
            Aggregate::class, 'afterEventPublished',
            SynchronousProjectionEventHandler::class, 'handle'
        );
    }
}