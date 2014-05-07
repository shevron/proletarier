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
        $workerPool = $this->createWorkerPool($config, $broker);

        $workerPool->launch();
        $broker->run();

        $result = new ConsoleModel();
        $result->setErrorLevel(0);

        return $result;
    }

    /**
     * Create the worker pool
     *
     * @param array  $config
     * @param Broker $broker
     *
     * @return WorkerPool
     * @throws \ErrorException
     */
    private function createWorkerPool(array $config, Broker $broker)
    {
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration array is missing the 'proletarier' key");
        }

        $poolSize = $config['proletarier']['worker']['pool_size'];
        $connect = $config['proletarier']['worker']['connect'];
        if ($connect === null) {
            $connect = $config['proletarier']['worker']['bind'];
            if ($connect === null) {
                $connect = $broker->getBackendAddress();
            }
        }

        return new WorkerPool($connect, $poolSize);
    }
}
