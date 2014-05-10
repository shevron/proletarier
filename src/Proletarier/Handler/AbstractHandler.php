<?php

namespace Proletarier\Handler;

use Zend\EventManager\EventInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

abstract class AbstractHandler implements ServiceLocatorAwareInterface
{
    use ServiceLocatorAwareTrait;

    /**
     * Handle an event
     *
     * @param EventInterface $event
     *
     * @return bool
     */
    abstract public function __invoke(EventInterface $event);
}
