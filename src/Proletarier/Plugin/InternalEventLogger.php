<?php

/**
 *    Copyright 2016 Shahar Evron
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace Proletarier\Plugin;

use Zend\EventManager\Event;
use Zend\EventManager\EventManagerInterface;
use Zend\EventManager\ListenerAggregateInterface;
use Zend\EventManager\ListenerAggregateTrait;
use Zend\Log\Logger;

class InternalEventLogger implements ListenerAggregateInterface
{
    use ListenerAggregateTrait;

    /**
     * Log object
     *
     * @var \Zend\Log\Logger
     */
    protected $log;

    public function __construct(Logger $log)
    {
        $this->log = $log;
    }

    /**
     * Attach one or more listeners
     *
     * Implementors may add an optional $priority argument; the EventManager
     * implementation will pass this to the aggregate.
     *
     * @param EventManagerInterface $events
     *
     * @return void
     */
    public function attach(EventManagerInterface $events)
    {
        $this->listeners[] = $events->attach('*', array($this, 'logEvent'));
    }

    /**
     * Log an event. This contains the logic for logging different Proletarier event types.
     *
     * @param Event $e
     */
    public function logEvent(Event $e)
    {
        switch($e->getName()) {
            case 'worker.error':
                $this->log->err("Worker error: {$e->getParam('exception')}");
                break;

            case 'worker.signal':
            case 'workerpool.signal':
                $pid = getmypid();
                $this->log->debug("Process $pid caught signal {$e->getParam('signal')}");
                break;

            case 'workerpool.worker-exited':
                $pid = $e->getParam('worker')->getProcessId();
                $code = $e->getParam('worker')->getExitStatus();
                $this->log->info("Worker process $pid exited with exit code $code");
                break;

            case 'worker.read':
                $bytes = strlen($e->getParam('payload'));
                $this->log->debug("Worker process read message $bytes bytes long: {$e->getParam('payload')}");
                break;

            default:
                $this->log->debug("Proletarier event: " . $e->getName());
                break;
        }
    }
}
