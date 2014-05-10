<?php

namespace Proletarier\Worker;

use Proletarier\Event;
use Proletarier\EventManagerAwareTrait;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\EventManager\EventManager;
use Zend\ServiceManager\ServiceLocatorAwareInterface;
use ZMQContext;
use ZMQSocket;
use ZMQSocketException;
use ZMQ;

class Worker implements WorkerInterface, ServiceLocatorAwareInterface
{
    /**
     * Glue in event manager awareness
     */
    use EventManagerAwareTrait;

    protected $connect;

    protected $running = false;

    protected $shutdown = false;

    protected $pid;

    protected $locator;

    /**
     * Event manager used for application events
     *
     * @var EventManager
     */
    protected $appEventManager;

    public function __construct($connect)
    {
        if (! function_exists('pcntl_fork')) {
            throw new \ErrorException("The pcntl extension is required but is not loaded");
        }

        $this->connect = $connect;
    }

    /**
     * Attach event listeners
     *
     * @param array $listeners
     *
     * @throws \InvalidArgumentException
     */
    public function attachListeners(array $listeners)
    {
        if (! $this->appEventManager) {
            $this->appEventManager = new EventManager();
        }

        foreach ($listeners as $listenerSpec) {
            if (is_array($listenerSpec)) {
                $event = $listenerSpec[0];
                $callback = $listenerSpec[1];
                if (isset($listenerSpec[3])) {
                    $priority = $listenerSpec[3];
                } else {
                    $priority = 1;
                }

                if (is_string($callback) && $this->getServiceLocator()->has($callback)) {
                    // Assume callback is an invokable that can be fetched from the SM
                    $callback = $this->getServiceLocator()->get($callback);
                }

                $this->appEventManager->attach($event, $callback, $priority);

            } elseif (is_string($listenerSpec) && $this->getServiceLocator()->has($listenerSpec)) {
                // Assume listener is a service implementing ListenerAggregateInterface
                $listener = $this->getServiceLocator()->has($listenerSpec);
                if ($listener instanceof ListenerAggregateInterface) {
                    $this->appEventManager->attach($listener);
                } else {
                    throw new \InvalidArgumentException("Invalid listener provided: '$listenerSpec'");
                }
            } else {
                throw new \InvalidArgumentException("Invalid listener provided: '$listenerSpec'");
            }
        }
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

        $this->running = true;
        $pid = pcntl_fork();
        if ($pid) {
            // parent process
            $this->pid = $pid;
            return $pid;
        } else {
            $this->configure();
            $this->hookSignalHandlers();
            $this->getEventManager()->trigger('worker.launch', $this);
            $this->mainLoop();
            // For now this is the safest way to make sure nothing gets
            // executed again inside the child process
            $this->getEventManager()->trigger('worker.exit', $this);
            exit(0);
        }
    }

    /**
     * Tell if the worker is currently running
     *
     * @return bool
     */
    public function isRunning()
    {
        // If in child process
        if (! $this->pid) {
            return $this->running;
        }

        // Check if process is still up (doesn't really send a kill signal)
        if (posix_kill($this->pid, 0)) {
            $this->running = true;
        } else {
            $this->running = false;
        }

        return $this->running;
    }

    /**
     * Get the worker process exit status (0 = normal)
     *
     * @return int
     */
    public function getExitStatus()
    {
        if (! $this->pid) {
            return;
        }

        $status = null;
        $pid = pcntl_waitpid($this->pid, $status, WNOHANG);

        if ($pid == -1) {
            return;
        }

        return pcntl_wexitstatus($status);
    }

    /**
     * Wait for the worker to terminate
     *
     * By default, this will block until the worker has exited. If $block is false, will not
     * block, and the return value will be either null if the process did not exit, or an
     * integer otherwise.
     *
     * You should check the return value of this function with === (indentity operator) to
     * differentiate between a null value representing no exit and a 0 which usually designates
     * a normal exit.
     *
     * The return value, if integer, will be positive or 0 if the process terminated by itself.
     * If the process has exited due to a signal, a negative signal number will be returned (e.g.
     * -11 will be returned for a SIGSEGV).
     *
     * @param bool $block
     *
     * @return int|null
     *
     * @todo Implement timeout for blocking
     */
    public function wait($block = true)
    {
        declare(ticks=1);

        if (! $this->pid) {
            return;
        }

        if ($block) {
            // Block until process exits
            $pid = pcntl_waitpid($this->pid, $status);
        } else {
            // Do not block at all
            $pid = pcntl_waitpid($this->pid, $status, WNOHANG);
        }

        if ($pid > 0) {
            // Process has exited
            if (pcntl_wifsignaled($status)) {
                return -(pcntl_wtermsig($status));
            } else {
                return pcntl_wexitstatus($status);
            }
        } else {
            // Process did not exit
            return null;
        }
    }

    /**
     * Get the process ID for this worker
     *
     * @return null|integer
     */
    public function getProcessId()
    {
        return $this->pid;
    }

    /**
     * Shut the worker down.
     *
     * @return $this
     */
    public function shutdown()
    {
        $this->shutdown = true;
        if ($this->pid) {
            if ($this->isRunning()) {
                // Signal child process to exit
                posix_kill($this->pid, SIGTERM);
            }
        } else {
            // Worker signaled to shutdown
            $this->getEventManager()->trigger('worker.shutdown', $this);
        }

        return $this;
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
     * Main worker event loop
     *
     * This listens for incoming requests and uses the router to dispatch them
     */
    protected function mainLoop()
    {
        // This is required for signals to work
        declare(ticks=1);

        $context = new ZMQContext();
        $receiver = new ZMQSocket($context, ZMQ::SOCKET_PULL);
        $receiver->connect($this->connect);

        while (! $this->shutdown) {
            $payload = null;
            try {
                $payload = $receiver->recv();
                $this->getEventManager()->trigger("worker.read", $this, array('payload' => $payload));
            } catch (ZMQSocketException $ex) {
                // This can mean we are shutting down, or an error occurred
                if ($ex->getCode() == 4) {
                    $this->shutdown();
                } else {
                    $this->getEventManager()->trigger("worker.read.error", $this, array('exception' => $ex));
                }
            }

            if ($payload) {
                try {
                    $event = Event::fromJson($payload);
                    $result = $this->appEventManager->trigger($event);
                    $this->getEventManager()->trigger('worker.result', $this, array('result' => $result));
                } catch (\InvalidArgumentException $ex) {
                    // Invalid message
                    $this->getEventManager()->trigger("worker.error", $this, array('exception' => $ex));
                } catch (\Exception $ex) {
                    $this->getEventManager()->trigger("worker.error", $this, array('exception' => $ex));
                }
            }
        }
    }

    /**
     * Configure worker. This is called after forking a new process.
     *
     */
    protected function configure()
    {
        $config = $this->getServiceLocator()->get('Config');
        $config = $config['proletarier'];
        $listeners = $config['listeners'];
        $this->attachListeners($listeners);
    }

    /**
     * Hook POSIX signal handlers for the forked worker process
     *
     */
    protected function hookSignalHandlers()
    {
        $worker = $this;
        $terminate = function ($signal) use ($worker) {
            $worker->getEventManager()->trigger('worker.signal', $worker, array('signal' => $signal));
            $worker->shutdown();
        };

        pcntl_signal(SIGINT, $terminate);
        pcntl_signal(SIGTERM, $terminate);
    }

    /**
     * Create a new Worker object form ServiceLocator
     *
     * @param ServiceLocatorInterface $locator
     *
     * @return Worker
     * @throws \ErrorException
     */
    public static function factory(ServiceLocatorInterface $locator)
    {
        $config = $locator->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration array is missing the 'proletarier' key");
        }
        $config = $config['proletarier'];

        $connect = $config['worker']['connect'];
        if ($connect === null) {
            $connect = $config['worker']['bind'];
        }

        $worker = new Worker($connect);
        return $worker;
    }
}
