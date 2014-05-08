<?php

namespace Proletarier\Controller;

use Proletarier\Broker;
use Proletarier\Worker\WorkerPool;
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
        $config = $this->getServiceLocator()->get('Config');

        /* @var $broker \Proletarier\Broker */
        $broker = $this->getServiceLocator()->get('Proletarier\Broker');
        $workerPool = $this->getServiceLocator()->get('Proletarier\WorkerPool');

        $workerPool->launch();
        $broker->run();

        $result = new ConsoleModel();
        $result->setErrorLevel(0);

        return $result;
    }
}
