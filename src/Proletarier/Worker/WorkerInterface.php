<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Worker;

use Zend\EventManager\EventManagerAwareInterface;

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
