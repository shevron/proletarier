<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Worker;

use Proletarier\EventManagerAwareTrait;

class ForkedWorker implements WorkerInterface
{
    use EventManagerAwareTrait;

    protected $pid;

    protected $worker;

    protected $isChildProcess;

    public function __construct(Worker $worker)
    {
        if (! function_exists('pcntl_fork')) {
            throw new \ErrorException("The pcntl extension must be loaded to use " . __CLASS__);
        }

        if ($worker->getEventManager()) {
            $this->setEventManager($worker->getEventManager());
        }

        $this->worker = $worker;
    }

    /**
     * Start the worker. In most cases this would fork out a process and return the process ID
     *
     * @return integer | null
     */
    public function launch()
    {
        if ($this->pid !== null) {
            return null;
        }

        $this->getEventManager()->trigger('worker.forking', $this);
        $pid = pcntl_fork();

        if ($pid) {
            // parent process
            $this->pid = $pid;
            return $pid;
        } else {
            // Child process - just run worker
            $this->isChildProcess = true;
            $this->hookSignalHandlers();
            $this->getEventManager()->trigger('worker.forked.child', $this);
            $this->worker->launch();
            $this->getEventManager()->trigger('worker.forked.child.exiting', $this);

            // For now, we just exit the child process after it has finished
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
        if ($this->isChildProcess) {
            return true;
        }

        if (! $this->pid) {
            return false;
        }

        // Check if process is still up (doesn't really send a kill signal)
        if (posix_kill($this->pid, 0)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Get the worker process exit status (0 = normal)
     *
     * @return int|null
     */
    public function getExitStatus()
    {
        if (! $this->pid) {
            return null;
        }

        $status = null;
        $pid = pcntl_waitpid($this->pid, $status, WNOHANG);

        if ($pid == -1) {
            return null;
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
            return null;
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
        if ($this->pid) {
            if ($this->isRunning()) {
                // Signal child process to exit
                posix_kill($this->pid, SIGTERM);
            }
        }

        return $this;
    }

    /**
     * Handler for SIGCHLD events, which usually indicate that a child process has exited
     *
     */
    public function childExitedHandler()
    {
        $this->getEventManager()->trigger('worker.forking.child-exited', $this->worker);
    }

    /**
     * Hook POSIX signal handlers for the forked worker process
     *
     */
    protected function hookSignalHandlers()
    {
        $terminate = function ($signal) {
            $this->getEventManager()->trigger('worker.forking.signal', $this->worker, array('signal' => $signal));
            $this->shutdown();
        };

        pcntl_signal(SIGINT, $terminate);
        pcntl_signal(SIGTERM, $terminate);
        pcntl_signal(SIGCHLD, [$this, 'childExitedHandler']);
    }
}
