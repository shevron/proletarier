<?php

namespace Proletarier\Worker;

use Proletarier\EventManagerAwareTrait;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class WorkerPool implements WorkerInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;

    protected $connect;

    protected $poolSize = 3;

    protected $running  = false;

    protected $shutdown = false;

    protected $locator;

    protected $workerProto;

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
     * Start the worker pool
     *
     * @return integer
     */
    public function launch()
    {
        if ($this->running) {
            return;
        }

        $this->getEventManager()->trigger('workerpool.launch', $this);
        for ($i = 0; $i < $this->poolSize; $i++) {
            $w = $this->createNewWorker();
            $w->setEventManager($this->getEventManager());

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
        foreach ($this->workers as $worker) {
            $worker->shutdown();
        }
    }

    /**
     * Wait for all workers to exit
     */
    public function wait($block = true)
    {
        $this->getEventManager()->trigger('workerpool.waiting', $this);
        while (! empty($this->workers)) {
            $exited = array();
            foreach ($this->workers as $pid => $worker) {
                $status = $worker->wait(false);
                if ($status !== null) {
                    $exited[] = $pid;
                    $this->getEventManager()->trigger('workerpool.worker-exited', $this, array($worker));
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
        $this->getEventManager()->trigger('workerpool.exit', $this);
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
     * Create a new worker
     *
     * @return Worker
     */
    protected function createNewWorker()
    {
        if ($this->workerProto) {
            return clone $this->workerProto;
        } else {
            return new Worker($this->connect);
        }
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
        }

        $pool = new WorkerPool($connect, $poolSize);
        $pool->workerProto = $locator->get('Proletarier\Worker');

        return $pool;
    }
}
