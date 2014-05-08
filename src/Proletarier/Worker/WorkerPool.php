<?php

namespace Proletarier\Worker;

use Proletarier\EventManagerAwareTrait;
use Proletarier\RouteStack;
use Zend\Mvc\Router\RouteInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class WorkerPool implements WorkerInterface
{
    use EventManagerAwareTrait;

    protected $connect;

    protected $poolSize = 3;

    /**
     * @var RouteInterface
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
     * @param RouteInterface $router
     *
     * @return $this
     */
    public function setRouter(RouteInterface $router)
    {
        $this->router = $router;
        return $this;
    }

    /**
     * Get the router object, or an empty Router object if not set
     *
     * @return RouteInterface
     */
    public function getRouter()
    {
        if (! $this->router) {
            $this->router = new RouteStack();
        }
        return $this->router;
    }

    /**
     * Start the worker pool
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
            foreach ($this->workers as $pid => $worker) {
                $status = $worker->wait(false);
                if ($status !== null) {
                    $exited[] = $pid;
                    $this->getEventManager()->trigger('worker.exited', $this, array($worker));
                }
            }

            foreach ($exited as $pid) {
                unset($this->workers[$pid]);
            }

            if (! $block) {
                return;
            }
            if (! empty($this->workers)) {
                usleep(10000);
            }
        }

        $this->running = false;
    }

    /**
     * @param ServiceLocatorInterface $locator
     *
     * @return WorkerPool
     * @throws \ErrorException
     */
    public static function factory(ServiceLocatorInterface $locator)
    {
        $config = $locator->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration array is missing the 'proletarier' key");
        }

        $poolSize = $config['proletarier']['worker']['pool_size'];
        $connect = $config['proletarier']['worker']['connect'];
        if ($connect === null) {
            $connect = $config['proletarier']['worker']['bind'];
            if ($connect === null) {
                $connect = $locator->get('Proletarier\Broker')->getBackendAddress();
            }
        }

        return new WorkerPool($connect, $poolSize);
    }
}
