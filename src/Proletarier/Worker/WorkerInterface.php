<?php

namespace Proletarier\Worker;

use Proletarier\Router;
use Zend\EventManager\EventManagerAwareInterface;

interface WorkerInterface extends EventManagerAwareInterface
{
    /**
     * Set the router
     *
     * @param Router $router
     * @return $this
     */
    public function setRouter(Router $router);

    /**
     * Get the router object
     *
     * @return Router
     */
    public function getRouter();

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
