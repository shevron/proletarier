<?php

namespace Proletarier;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;

trait EventManagerAwareTrait
{
    protected $eventManager = null;

    /**
     * Inject an EventManager instance
     *
     * @param  EventManagerInterface $eventManager
     * @return $this
     */
    public function setEventManager(EventManagerInterface $eventManager)
    {
        $this->eventManager = $eventManager;
        return $this;
    }

    /**
     * Retrieve the event manager
     *
     * Lazy-loads an EventManager instance if none registered.
     *
     * @return EventManagerInterface
     */
    public function getEventManager()
    {
        if (! $this->eventManager) {
            $this->eventManager = new EventManager();
        }

        return $this->eventManager;
    }
}
