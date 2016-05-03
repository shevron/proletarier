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

namespace Proletarier\Event;

use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\EventManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;

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
            if ($this instanceof ServiceLocatorAwareInterface) {
                $this->eventManager = $this->getServiceLocator()->get('Proletarier\EventManager');
            } else {
                $this->eventManager = new EventManager();
            }
        }

        return $this->eventManager;
    }
}
