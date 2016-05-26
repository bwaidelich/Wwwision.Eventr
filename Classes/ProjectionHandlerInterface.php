<?php
namespace Wwwision\Eventr;

use TYPO3\Flow\Annotations as Flow;
use Wwwision\Eventr\EventHandler\EventHandlerInterface;

interface ProjectionHandlerInterface extends EventHandlerInterface
{

//    public function __construct(array $options);

    /**
     * Returns the state for a given Aggregate. This can be used as Read/View Model
     *
     * @param string $id
     * @return array
     */
    public function getStateFor($id);

    /**
     * Resets the states (i.e. removing all Read Models)
     *
     * @return void
     */
    public function resetState();
}