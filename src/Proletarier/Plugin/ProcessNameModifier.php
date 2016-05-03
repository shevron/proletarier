<?php

/**
 *    Copyright 2016 Shahar Evron
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
