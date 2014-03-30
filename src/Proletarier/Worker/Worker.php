<?php

namespace Proletarier\Worker;

use Proletarier\EventManagerAwareTrait;
use Proletarier\Message\Request;
use Zend\Mvc\Router\RouteInterface;
use ZMQContext;
use ZMQSocket;
use ZMQSocketException;
use ZMQ;

class Worker implements WorkerInterface
{
    /**
     * Glue in event manager awareness
     */
    use EventManagerAwareTrait;

    protected $connect;

    protected $router;

    protected $running = false;

    protected $shutdown = false;

    protected $pid;

    public function __construct($connect)
    {
        if (! function_exists('pcntl_fork')) {
            throw new \ErrorException("The pcntl extension is required but is not loaded");
        }

        $this->connect = $connect;
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
     * Get the router object
     *
     * @return RouteInterface
     */
    public function getRouter()
    {
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

        $this->running = true;
        $pid = pcntl_fork();
        if ($pid) {
            // parent process
            $this->pid = $pid;
            return $pid;
        } else {
            $this->hookSignalHandlers();
            $this->mainLoop();
            // For now this is the safest way to make sure nothing gets
            // executed again inside the child process
            $this->getEventManager()->trigger('exit', $this);
            exit(0);
        }
    }

    public function isRunning()
    {
        // If in
        if (! $this->pid) return $this->running;

        // Check if process is still up (doesn't really send a kill signal)
        if (posix_kill($this->pid, 0)) {
            $this->running = true;
        } else {
            $this->running = false;
        }

        return $this->running;
    }

    public function getExitStatus()
    {
        if (! $this->pid) return;
        $status = null;
        $pid = pcntl_waitpid($this->pid, $status, WNOHANG);
        if ($pid == -1) return;
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

        if (! $this->pid) return;
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
     * Shut the worker down.
     *
     * @return mixed
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
            $this->getEventManager()->trigger(__FUNCTION__, $this);
        }

        return $this;
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

            try {
                $string = $receiver->recv();
                $this->getEventManager()->trigger("recv.read", $this, array('payload' => $string));
            } catch (ZMQSocketException $ex) {
                // This can mean we are shutting down, or an error occurred
                // TODO: handle shutdowns more gracefully (not with an error)
                $this->getEventManager()->trigger("recv.error", $this, array('exception' => $ex));
                break;
            }

            try {
                $request = Request::fromString($string);
                $routeMatch = $this->getRouter()->match($request);
                if (! $routeMatch) {
                    $this->getEventManager()->trigger('route.notfound', $this, array('request' => $request));
                } else {
                    $this->getEventManager()->trigger('route', $this, array('routeMatch' => $routeMatch,
                                                                            'request' => $request));
                    $this->getEventManager()->trigger("route.post", $this, array('routeMatch' => $routeMatch,
                                                                                 'request' => $request));
                }

            } catch (\Exception $ex) {
                $this->getEventManager()->trigger("route.error", $this, array('exception' => $ex,
                                                                              'request' => $request));
            }


        }
    }

    protected function hookSignalHandlers()
    {
        $worker = $this;
        pcntl_signal(SIGTERM, function($signal) use ($worker) {
            $worker->shutdown();
        });
    }
}
