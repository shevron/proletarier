<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Worker;

use Zend\EventManager\EventManager;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\Serializer\Adapter\AdapterInterface as SerializerInterface;
use Zend\Serializer\Adapter\Json;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\Json\Json as ZendJson;

class WorkerFactory
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

        $eventManager = $this->createEventManager($services, $config);
        $serializer = $this->createSerializer($services, $config);

        $worker = new Worker($connect, $eventManager, $serializer);

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
                if (isset($listenerSpec[3])) {
                    $priority = $listenerSpec[3];
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
