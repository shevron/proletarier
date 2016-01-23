<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Worker;

use ZMQ;
use ZMQContext;
use ZMQSocket;
use Proletarier\EventManagerAwareTrait;
use Proletarier\Event;
use Zend\EventManager\EventManager;
use Zend\EventManager\EventManagerInterface;
use Zend\Serializer\Adapter\Json;
use Zend\Serializer\Adapter\AdapterInterface as SerializerInterface;

class Worker implements WorkerInterface
{
    /**
     * Glue in event manager awareness
     */
    use EventManagerAwareTrait;

    /**
     * ZeroMQ socket to connect to
     *
     * @var string
     */
    protected $connect;

    /**
     * Termination flag
     *
     * @var bool
     */
    protected $shutdown = false;

    /**
     * Serializer object
     *
     * @var SerializerInterface
     */
    protected $serializer;

    /**
     * Event manager used for application events
     *
     * @var EventManagerInterface
     */
    protected $appEventManager;

    /**
     * @param string                     $connect
     * @param EventManagerInterface|null $eventManager
     * @param SerializerInterface        $serializer
     */
    public function __construct(
        $connect,
        EventManagerInterface $eventManager = null,
        SerializerInterface $serializer = null
    )
    {
        if (! $eventManager) {
            $eventManager = new EventManager();
        }
        if (! $serializer) {
            $serializer = new Json();
        }
        $this->appEventManager = $eventManager;
        $this->serializer = $serializer;
        $this->connect = $connect;
    }

    /**
     * Start the worker. In most cases this would fork out a process and return the process ID
     *
     * @return integer
     */
    public function launch()
    {
        $this->getEventManager()->trigger('worker.launch', $this);
        $this->mainLoop();
        $this->getEventManager()->trigger('worker.exit', $this);
    }

    /**
     * Shut the worker down.
     *
     * @return $this
     */
    public function shutdown()
    {
        $this->shutdown = true;
        // Worker signaled to shutdown
        $this->getEventManager()->trigger('worker.shutdown', $this);

        return $this;
    }

    /**
     * Main worker event loop
     *
     * This listens for incoming requests and uses the router to dispatch them
     */
    protected function mainLoop()
    {
        // This is required for signals to work
        declare(ticks=1);

        $context = new ZMQContext();
        $receiver = new ZMQSocket($context, ZMQ::SOCKET_PULL);
        $receiver->connect($this->connect);

        while (! $this->shutdown) {
            $payload = null;
            try {
                $payload = $receiver->recv();
                $this->getEventManager()->trigger("worker.read", $this, array('payload' => $payload));
            } catch (\ZMQSocketException $ex) {
                // This can mean we are shutting down, or an error occurred
                if ($ex->getCode() == 4) {
                    $this->shutdown();
                } else {
                    $this->getEventManager()->trigger("worker.read.error", $this, array('exception' => $ex));
                }
            }

            if ($payload) {
                try {
                    $event = $this->serializer->unserialize($payload);
                    if (is_array($event)) {
                        $event = (new Event())->exchangeArray($event);
                    }
                    $result = $this->appEventManager->trigger($event);
                    $this->getEventManager()->trigger('worker.result', $this, array('result' => $result));
                } catch (\Exception $ex) {
                    $this->getEventManager()->trigger("worker.error", $this, array('exception' => $ex));
                }
            }
        }
    }
}
