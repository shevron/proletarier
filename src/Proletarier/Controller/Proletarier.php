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

namespace Proletarier\Controller;

use Proletarier\Plugin\InternalEventLogger;
use Proletarier\Plugin\ProcessNameModifier;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractConsoleController;
use Zend\View\Model\ConsoleModel;

class Proletarier extends AbstractConsoleController
{
    public function runBrokerAction()
    {
        $this->initEvents();

        /* @var $broker \Proletarier\Broker\Broker */
        $broker = $this->getServiceLocator()->get('Proletarier\Broker');

        $broker->bind();
        $broker->run();

        $result = new ConsoleModel();
        $result->setErrorLevel(0);

        return $result;
    }

    public function runWorkerAction()
    {
        $this->initEvents();

        /* @var $worker \Proletarier\Worker\Worker */
        $worker = $this->getServiceLocator()->get('Proletarier\Worker');

        // If we can, hook SIGTERM and SIGINT to the worker's shutdown action
        if (function_exists('pcntl_signal')) {
            pcntl_signal(SIGTERM, [$worker, 'shutdown']);
            pcntl_signal(SIGINT, [$worker, 'shutdown']);
        }

        try {
            $worker->launch();
        } catch (\Exception $e) {
            $worker->shutdown();
            throw $e;
        }

        $result = new ConsoleModel();
        $result->setErrorLevel(0);

        return $result;
    }

    /**
     * Trigger an event - used as a Proletarier client, mainly for testing purposes
     *
     */
    public function triggerAction()
    {
        /* @var $client \Proletarier\Client\ClientInterface */
        $client = $this->getServiceLocator()->get('Proletarier\Client');

        $event = $this->getRequest()->getParam('event');
        $params = $this->getRequest()->getParam('params');

        if ($params) {
            $params = json_decode($params, true);
        }

        if (! $params) {
            $params = array();
        }

        $client->trigger($event, $params);
    }

    /**
     * Initialize some important events before running
     *
     */
    private function initEvents()
    {
        $serviceManager = $this->getServiceLocator();
        /* @var $eventManager EventManagerInterface */
        $eventManager = $serviceManager->get('Proletarier\EventManager');

        /* @var $logger \Zend\Log\Logger */
        $logger = $serviceManager->get('Proletarier\Log');
        $eventManager->attach(new InternalEventLogger($logger));
        $eventManager->attach(new ProcessNameModifier([
            'broker.bind.pre' => 'Proletarier broker',
            'worker.launch'   => 'Proletarier worker'
        ]));
    }
}
