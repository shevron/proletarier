<?php

/**
 * shoppimon-frontend
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar
 */

namespace Proletarier\Plugin;

use Zend\EventManager\EventInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;

class ProcessNameModifier implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * @var array Event name => Process title map
     */
    protected $eventMap = [];

    /**
     * @param array $events
     */
    public function __constructs(array $events)
    {
        $this->eventMap = $events;
    }

    /**
     * Attach to events
     *
     * @param EventManagerInterface $eventManager
     */
    public function attach(EventManagerInterface $eventManager)
    {
        foreach($this->eventMap as $event => $name) {
            $eventManager->attach($event, [$this, 'setProcessTitle']);
        }
    }

    /**
     * Set process title based on event name
     *
     * @param EventInterface $event
     */
    public function setProcessTitle(EventInterface $event)
    {
        // cli_set_process_title is only available in some setups (PHP 5.5+)
        if (! function_exists('cli_set_process_title')) {
            return;
        }

        if (isset($this->eventMap[$event->getName()])) {
            cli_set_process_title($this->eventMap[$event->getName()]);
        }
    }
}
