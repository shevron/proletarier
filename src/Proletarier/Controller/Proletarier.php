<?php

namespace Proletarier\Controller;

use Proletarier\LoggingListener;
use Zend\EventManager\EventManagerInterface;
use Zend\Mvc\Controller\AbstractConsoleController;
use Zend\View\Model\ConsoleModel;

class Proletarier extends AbstractConsoleController
{
    /**
     * Main Proletarier console action - run the broker and worker pool
     *
     * @return array|void
     */
    public function runAction()
    {
        $this->initEvents();

        /* @var $broker \Proletarier\Broker */
        $broker = $this->getServiceLocator()->get('Proletarier\Broker');

        /* @var $workerPool \Proletarier\Worker\WorkerPool */
        $workerPool = $this->getServiceLocator()->get('Proletarier\WorkerPool');
        $workerPool->launch();

        try {
            $broker->bind();
            $broker->run();
        } finally {
            // Shut the workers down
            $workerPool->shutdown();
            $workerPool->wait();
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
        $eventManager->attach(new LoggingListener($logger));

        // If we can (PHP 5.5 +), set the process title of workers after they launch
        if (function_exists('cli_set_process_title')) {
            $eventManager->attach('workerpool.launch', function () {
                cli_set_process_title('Proletarier master');
            });

            $eventManager->attach('worker.launch', function () {
                cli_set_process_title('Proletarier worker');
            });
        }
    }
}
