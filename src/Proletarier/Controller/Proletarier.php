<?php

namespace Proletarier\Controller;

use Zend\Console\Request;
use Zend\Mvc\Controller\AbstractActionController;
use Proletarier\LoggingListener;
use Zend\View\Model\ConsoleModel;

class Proletarier extends AbstractActionController
{
    /**
     * Main Proletarier console action - run the broker
     *
     * @return array|void
     */
    public function runAction()
    {
        // Only work for console requests
        if (! $this->getRequest() instanceof Request) {
            return;
        }
        $this->initEvents();

        /* @var $broker \Proletarier\Broker */
        $broker = $this->getServiceLocator()->get('Proletarier\Broker');

        /* @var $workerPool \Proletarier\Worker\WorkerPool */
        $workerPool = $this->getServiceLocator()->get('Proletarier\WorkerPool');
        $workerPool->launch();

        $broker->run();

        // Shut the workers down
        $workerPool->shutdown();
        $workerPool->wait();

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
        // Only work for console requests
        if (! $this->getRequest() instanceof Request) {
            return;
        }

        /* @var $client \Proletarier\Client */
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
        $eventManager = $serviceManager->get('Proletarier\EventManager'); /* @var $eventManager EventManager */

        $eventManager->attach(new LoggingListener($serviceManager->get('Proletarier\Log')));

        // If we can (PHP 5.5 +), set the process title of workers after they launch
        if (function_exists('cli_set_process_title')) {
            $eventManager->attach('workerpool.launch', function ($e) {
                cli_set_process_title('Proletarier master');
            });

            $eventManager->attach('worker.launch', function ($e) {
                cli_set_process_title('Proletarier worker');
            });
        }
    }
}
