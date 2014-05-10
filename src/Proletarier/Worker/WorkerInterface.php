<?php

namespace Proletarier\Worker;

use Zend\EventManager\EventManagerAwareInterface;
use Zend\Mvc\Router\RouteInterface;

interface WorkerInterface extends EventManagerAwareInterface
{
    /**
     * Start the worker. In most cases this would fork out a process and return the process ID
     *
     * @return integer
     */
    public function launch();

    /**
     * Shut the worker down.
     *
     * @return mixed
     */
    public function shutdown();

}
