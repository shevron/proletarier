<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Controller;

use Proletarier\Plugin\InternalEventLogger;
use Proletarier\Plugin\ProcessNameModifier;
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

        /* @var $broker \Proletarier\Broker\Broker */
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
        $eventManager->attach(new InternalEventLogger($logger));
        $eventManager->attach(new ProcessNameModifier([
            'workerpool.launch' => 'Proletarier master',
            'worker.launch'     => 'Proletarier worker'
        ]));
    }
}
