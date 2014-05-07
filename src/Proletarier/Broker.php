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
        $this->getEventManager()->trigger(__FUNCTION__ . '.pre', $this);

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

        $this->getEventManager()->trigger(__FUNCTION__, $this);

        // This should block forever
        $device->run();

        // Not sure this is ever called?
        $this->getEventManager()->trigger(__FUNCTION__ . '.post', $this);
    }

    /**
     * Idle callback
     *
     * This is called periodically if setIdleTimeout has been called, and triggers
     * an event that can be caught by the event manager.
     */
    public function idle()
    {
        $this->getEventManager()->trigger(__FUNCTION__, $this);
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

        $workerBind = $config['proletarier']['worker']['bind'];
        if (! $workerBind) {
            $workerBind = 'ipc://' . tempnam(sys_get_temp_dir(), 'proletarier_ipc_');
        }

        /* @var $broker Broker */
        $broker = new static($config['proletarier']['broker']['bind'], $workerBind);
        return $broker;
    }
}
