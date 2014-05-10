<?php

namespace Proletarier\Handler;

use Zend\EventManager\Event;

class EventLogger extends AbstractHandler
{
    /**
     * Handle an event
     *
     * @param Event $event
     *
     * @return bool
     */
    public function __invoke(Event $event)
    {
        /* @var $logger \Zend\Log\Logger */
        $logger = $this->getServiceLocator()->get('Proletarier\Log');
        $logger->debug($event->getName());
    }
}
