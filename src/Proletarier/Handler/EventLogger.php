<?php

namespace Proletarier\Handler;

use Zend\EventManager\EventInterface;

class EventLogger extends AbstractHandler
{
    /**
     * Handle an event
     *
     * @param EventInterface $event
     *
     * @return bool
     */
    public function __invoke(EventInterface $event)
    {
        /* @var $logger \Zend\Log\Logger */
        $log = $this->getServiceLocator()->get('Proletarier\Log');
        $log->debug("Application event: " . $event->getName());
    }
}
