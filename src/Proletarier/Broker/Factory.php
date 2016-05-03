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

namespace Proletarier\Broker;

use Zend\ServiceManager\ServiceLocatorInterface;

class Factory
{
    /**
     * @param $services ServiceLocatorInterface
     * @return Broker
     * @throws \ErrorException
     */
    public function __invoke($services)
    {
        $config = $services->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration is missing the 'proletarier' key");
        }
        $config = $config['proletarier'];

        /* @var $events \Zend\EventManager\EventManagerInterface */
        $events = $services->get('Proletarier\EventManager');
        $broker = new Broker($config['broker']['bind'], $config['worker']['bind']);
        $broker->setEventManager($events);

        return $broker;
    }
}
