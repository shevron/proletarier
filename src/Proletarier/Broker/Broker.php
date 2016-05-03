<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Broker;

use ZMQ;
use ZMQContext;
use ZMQSocket;
use ZMQDevice;
use ZMQDeviceException;
use Zend\EventManager\EventManagerAwareInterface;
use Proletarier\Event\EventManagerAwareTrait;

class Broker implements EventManagerAwareInterface
{
    /**
     * Glue in event manager awareness
     */
    use EventManagerAwareTrait;

    /**
     * @var string Address for front-end connections (connections from clients)
     */
    protected $frontAddress;

    /**
     * @var string Address for back-end connections (connections from workers)
     */
    protected $backAddress;

    /**
     * @var ZMQContext
     */
    protected $context;

    /**
     * @var ZMQDevice
     */
    protected $device;

    /**
     * @var integer
     */
    protected $idleTimeout = null;

    public function __construct($frontAddress, $backAddress)
    {
        $this->frontAddress = $frontAddress;
        $this->backAddress = $backAddress;
    }

    /**
     * Get the backend address. This is useful if the backend address is dynamically set.
     *
     * @return string
     */
    public function getBackendAddress()
    {
        return $this->backAddress;
    }

    /**
     * Set the idle event time interval in milliseconds
     *
     * If set, an idle event will be triggered by the event manager, and the broker object
     * will be passed to it every N milliseconds if the broker is idle
     *
     * @param integer  $timeout
     *
     * @return $this
     */
    public function setIdleTimeout($timeout)
    {
        $this->idleTimeout = $timeout;
        return $this;
    }

    /**
     * Create the ZeroMQ context and bind sockets
     *
     * This can be called before calling run - if not it will be called automatically. If called
     * multiple times, this has no effect.
     *
     * The 'broker.bind.pre' and 'broker.bind.post' events are emitted before and after binding.
     *
     * @return boolean
     */
    public function bind()
    {
        if (! $this->context) {
            $this->getEventManager()->trigger('broker.bind.pre', $this);

            $this->context = new ZMQContext();

            $frontend = new ZMQSocket($this->context, ZMQ::SOCKET_PULL);
            $frontend->bind($this->frontAddress);
            $backend = new ZMQSocket($this->context, ZMQ::SOCKET_PUSH);
            $backend->bind($this->backAddress);

            $this->device = new ZMQDevice($frontend, $backend);
            if ($this->idleTimeout) {
                $this->device->setIdleTimeout($this->idleTimeout)
                             ->setIdleCallback(array($this, 'idle'));
            }

            $this->getEventManager()->trigger('broker.bind.post', $this);

            return true;
        }

        return false;
    }

    /**
     * Run the broker
     *
     */
    public function run()
    {
        if (! $this->device) {
            $this->bind();
        }

        $this->getEventManager()->trigger('broker.run.pre', $this);
        $this->getEventManager()->trigger('broker.run', $this);

        try {
            // This blocks until interrupted
            $this->device->run();
        } catch (ZMQDeviceException $e) {
            if ($e->getCode() != 4) {
                throw $e;
            }
        }

        $this->getEventManager()->trigger('broker.run.post', $this);
    }

    /**
     * Idle callback
     *
     * This is called periodically if setIdleTimeout has been called, and triggers
     * an event that can be caught by the event manager.
     */
    public function idle()
    {
        $this->getEventManager()->trigger('broker.idle', $this);
    }
}
