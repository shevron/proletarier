<?php

namespace Proletarier\Worker;

use Proletarier\EventManagerAwareTrait;
use Proletarier\Router;

class WorkerPool implements WorkerInterface
{
    use EventManagerAwareTrait;

    protected $connect;

    protected $poolSize = 3;

    /**
     * @var Router
     */
    protected $router;

    protected $running  = false;

    protected $shutdown = false;

    /**
     * @var Worker[]
     */
    protected $workers  = array();

    public function __construct($connect, $poolSize)
    {
        $this->connect = $connect;
        $this->poolSize = $poolSize;
    }

    /**
     * Set the router
     *
     * @param Router $router
     *
     * @return $this
     */
    public function setRouter(Router $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * Get the router object, or an empty Router object if not set
     *
     * @return Router
     */
    public function getRouter()
    {
        if (! $this->router) {
            $this->router = new Router();
        }
        return $this->router;
    }

    /**
     * Start the worker. In most cases this would fork out a process and return the process ID
     *
     * @return integer
     */
    public function launch()
    {
        if ($this->running) {
            return;
        }

        for ($i = 0; $i < $this->poolSize; $i++) {
            $w = new Worker($this->connect);
            $w->setEventManager($this->getEventManager());
            $w->setRouter($this->getRouter());
            $pid = $w->launch();
            $this->workers[$pid] = $w;
        }

        $this->running = true;
    }

    /**
     * Shut down all workers in the pool
     *
     * @return mixed
     */
    public function shutdown()
    {
        $this->shutdown = true;

        // Signal all workers to shut down
        foreach($this->workers as $pid => $worker) {
            $worker->shutdown();
        }
    }

    /**
     * Wait for all workers to exit
     */
    public function wait($block = true)
    {
        while (! empty($this->workers)) {
            $exited = array();
            foreach($this->workers as $pid => $worker) {
                $status = $worker->wait(false);
                if ($status !== null) {
                    $exited[] = $pid;
                    $this->getEventManager()->trigger('worker.exited', $this, array($worker));
                }
            }

            foreach($exited as $pid) {
                unset($this->workers[$pid]);
            }

            if (! $block) return;
            if (! empty($this->workers)) {
                usleep(10000);
            }
        }

        $this->running = false;
    }
}
