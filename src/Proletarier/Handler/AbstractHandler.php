<?php

namespace Proletarier\Handler;

use Zend\EventManager\EventInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractHandler implements ServiceLocatorAwareInterface
{
    protected $serviceLocator;

    /**
     * Handle an event
     *
     * @param EventInterface $event
     *
     * @return bool
     */
    abstract public function __invoke(EventInterface $event);

    /**
     * Set service locator
     *
     * @param  ServiceLocatorInterface $serviceLocator
     * @return $this
     */
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator)
    {
        $this->serviceLocator = $serviceLocator;
        return $this;
    }

    /**
     * Get service locator
     *
     * @return ServiceLocatorInterface
     */
    public function getServiceLocator()
    {
        return $this->serviceLocator;
    }
}
