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
