<?php

namespace Proletarier;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\EventManager\EventManagerInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use ZMQ;
use ZMQContext;
use ZMQSocket;
use ZMQDevice;
use ZMQDeviceException;

class Broker implements EventManagerAwareInterface, ServiceLocatorAwareInterface
{
    /**
     * Glue in event manager awareness
     */
    use EventManagerAwareTrait;

    /**
     * @var ServiceLocatorInterface
     */
    protected $locator;

    /**
     * @var string Address for front-end connections (connections from clients)
     */
    protected $frontAddress;

    /**
     * @var string Address for back-end connections (connections from workers)
     */
    protected $backAddress;

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
     * Run the broker
     *
     */
    public function run()
    {
        $this->getEventManager()->trigger('broker.run.pre', $this);

        $context = new ZMQContext();

        $frontend = new ZMQSocket($context, ZMQ::SOCKET_PULL);
        $frontend->bind($this->frontAddress);
        $backend = new ZMQSocket($context, ZMQ::SOCKET_PUSH);
        $backend->bind($this->backAddress);

        $device = new ZMQDevice($frontend, $backend);
        if ($this->idleTimeout) {
            $device->setIdleTimeout($this->idleTimeout)
                   ->setIdleCallback(array($this, 'idle'));
        }

        $this->hookShutdownSignals();
        $this->getEventManager()->trigger('broker.run', $this);

        try {
            // This blocks until interrupted
            $device->run();
        } catch (ZMQDeviceException $e) {
            if ($e->getCode() == 4) {
                // Interrupt
                $this->getEventManager()->trigger('broker.run.post', $this);
            } else {
                throw $e;
            }
        }
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

    /**
     * Set service locator
     *
     * @param ServiceLocatorInterface $serviceLocator
     * @return $this
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->locator = $serviceLocator;
        return $this;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->locator;
    }

    /**
     * Hook posix shutdown signal handlers
     *
     */
    protected function hookShutdownSignals()
    {
        $terminate = function ($signal) {
            $this->getEventManager()->trigger('broker.signal', $this, array('signal' => $signal));
        };

        pcntl_signal(SIGTERM, $terminate);
        pcntl_signal(SIGINT, $terminate);
    }

    /**
     * @param ServiceLocatorInterface $locator
     *
     * @throws \ErrorException
     * @return Broker
     */
    public static function factory(ServiceLocatorInterface $locator)
    {
        $config = $locator->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration is missing the 'proletarier' key");
        }

        /* @var $broker Broker */
        $broker = new static($config['proletarier']['broker']['bind'], $config['proletarier']['worker']['bind']);

        return $broker;
    }
}
