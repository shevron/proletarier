<?php

namespace Proletarier\Controller;

use Zend\Console\Request;
use Zend\Mvc\Controller\AbstractActionController;
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
}
