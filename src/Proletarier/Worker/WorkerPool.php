<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Worker;

use Proletarier\Event\EventManagerAwareTrait;
use Zend\EventManager\EventInterface;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorAwareTrait;
use Zend\ServiceManager\ServiceLocatorInterface;

class WorkerPool implements WorkerInterface, ServiceLocatorAwareInterface
{
    use EventManagerAwareTrait;
    use ServiceLocatorAwareTrait;

    protected $poolSize;

    protected $running  = false;

    protected $shutdown = false;

    protected $workerProto;

    /**
     * @var ForkedWorker[]
     */
    protected $workers  = array();

    public function __construct(Worker $workerProto, $poolSize)
    {
        $this->workerProto = $workerProto;
        $this->poolSize = (int) $poolSize;
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

        // Handle exiting children
        $this->getEventManager()->attach('worker.forking.child-exited', [$this, 'childExitHandler']);

        $this->getEventManager()->trigger('workerpool.launch', $this);
        for ($i = 0; $i < $this->poolSize; $i++) {
            $this->launchWorker();
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
     *
     * @param boolean $block
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
                    $this->getEventManager()->trigger('workerpool.worker-exited', $this, array('worker' => $worker));
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
     * Handler for child exit event.
     *
     * If the child exited prematurely, will respawn a new one.
     *
     * @param EventInterface $event
     */
    public function childExitHandler(EventInterface $event)
    {
        /* @var ForkedWorker $child */
        $child = $event->getTarget();
        $pid = $child->getProcessId();
        $child->wait();

        unset($this->workers[$pid]);
        if (! $this->shutdown) {
            $this->launchWorker();
        }
    }

    /**
     * Create a new worker, start it and add it to workers array
     *
     * @return ForkedWorker
     */
    protected function launchWorker()
    {
        $worker = new ForkedWorker(clone $this->workerProto);
        $pid = $worker->launch();
        $this->workers[$pid] = $worker;

        return $worker;
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
        $worker = $locator->get('Proletarier\Worker'); /* @var $worker Worker */
        $pool = new WorkerPool($worker, $poolSize);

        return $pool;
    }
}
