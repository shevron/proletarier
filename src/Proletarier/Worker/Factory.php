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

namespace Proletarier\Worker;

use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Serializer\Adapter\AdapterInterface as SerializerInterface;
use Zend\Serializer\Adapter\Json;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Json\Json as ZendJson;

class Factory
{
    /**
     * @param  ServiceLocatorInterface $services
     * @return Worker
     * @throws \ErrorException
     */
    public function __invoke($services)
    {
        $config = $services->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration array is missing the 'proletarier' key");
        }
        $config = $config['proletarier'];

        $connect = $config['worker']['connect'];
        if ($connect === null) {
            $connect = $config['worker']['bind'];
        }

        $appEventManager = $this->createEventManager($services, $config);
        $serializer = $this->createSerializer($services, $config);

        $worker = new Worker($connect, $appEventManager, $serializer);

        // Attach internal event manager
        /* @var $events \Zend\EventManager\EventManagerInterface */
        $events = $services->get('Proletarier\EventManager');
        $worker->setEventManager($events);

        return $worker;
    }

    public function createEventManager($services, array $config)
    {
        $eventManager = new EventManager();
        $listeners = $config['listeners'];

        foreach ($listeners as $listenerSpec) {
            if (is_array($listenerSpec)) {
                $event = $listenerSpec[0];
                $callback = $listenerSpec[1];
                if (isset($listenerSpec[2])) {
                    $priority = $listenerSpec[2];
                } else {
                    $priority = 1;
                }

                if (is_string($callback)) {
                    $callback = $this->getHandlerInstance($services, $callback);
                }

                $eventManager->attach($event, $callback, $priority);

            } elseif (is_string($listenerSpec)) {
                $listener = $this->getHandlerInstance($services, $listenerSpec);
                if ($listener instanceof ListenerAggregateInterface) {
                    $eventManager->attach($listener);
                } else {
                    throw new \InvalidArgumentException("Invalid listener provided: '$listenerSpec'");
                }
            } else {
                throw new \InvalidArgumentException("Invalid listener provided: '$listenerSpec'");
            }
        }

        return $eventManager;
    }

    /**
     * Create serializer object from config or default to Json
     *
     * @param  ServiceLocatorInterface $services
     * @param  array $config
     * @return SerializerInterface
     */
    protected function createSerializer($services, array $config)
    {
        $serializer = null;

        if (isset($config['serializer'])) {
            if ($services->has($config['serializer'])) {
                $serializer = $services->get($config['serializer']);
            } elseif (class_exists($config['serializer'])) {
                $serializer = new $config['serializer'];
            } else {
                throw new \InvalidArgumentException("Provided 'serializer' is not a class or a service name");
            }

            if (! $serializer instanceof SerializerInterface) {
                throw new \InvalidArgumentException(
                    "Provider 'serializer' does not implement Serializer\\AdapterInterface"
                );
            }
        }

        if (! $serializer) {
            $serializer = new Json();
        }

        if ($serializer instanceof Json) {
            $serializer->setOptions(['object_decode_type' => ZendJson::TYPE_ARRAY]);
        }

        return $serializer;
    }

    /**
     * @param  ServiceLocatorInterface $services
     * @param  string                  $handler
     *
     * @return callable|ListenerAggregateInterface
     */
    protected function getHandlerInstance($services, $handler)
    {
        $instance = null;

        if ($services->has($handler)) {
            // Assume callback is an invokable that can be fetched from the SM
            $instance = $services->get($handler);
        } elseif (class_exists($handler)) {
            $instance = new $handler();
            if ($instance instanceof ServiceLocatorAwareInterface) {
                $instance->setServiceLocator($services);
            }
        }

        return $instance;
    }
}
