<?php

/**
 * Proletarier Module Configuration File
 */

return array(
    'console' => array(
        'router' => array(
            'routes' => array(
                'proletarier-run' => array(
                    'options' => array(
                        'route' => 'proletarier run',
                        'defaults' => array(
                            'controller' => 'proletarier',
                            'action' => 'run'
                        )
                    )
                )
            )
        )
    ),

    'controllers' => array(
        'invokables' => array(
            'proletarier' => 'Proletarier\Controller\Proletarier'
        )
    ),

    'service_manager' => array(
        'factories' => array(
            'Proletarier\Broker'       => array('Proletarier\Broker', 'factory'),
            'Proletarier\Worker'       => array('Proletarier\Worker\Worker', 'factory'),
            'Proletarier\WorkerPool'   => array('Proletarier\Worker\WorkerPool', 'factory'),
//            'Proletarier\Client'       => array('Proletarier\Client', 'factory'),
            'Proletarier\EventManager' => 'Zend\Mvc\Service\EventManagerFactory',
        ),

        'abstract_factories' => array(
            'Proletarier\Log'       => 'Zend\Log\LoggerAbstractServiceFactory',
        ),

        'invokables' => array(
            'Proletarier\Handler\EventLogger',
        ),
    ),

    'proletarier' => array(
        'handlers' => array(
            array('*', 'Proletarier\Handler\EventLogger'),
        ),

        'broker' => array(
            'bind'    => 'tcp://127.0.0.1:9105',
            'connect' => null,
        ),

        'worker' => array(
            'pool_size' => 5,
            'bind'      => null,
            'connect'   => null,
        ),
    )
    ),

    'log' => array(
        'Proletarier\Log' => array(
            'writers' => array(
                'stderr' => array(
                    'name' => 'stream',
                    'options' => array(
                        'stream' => 'php://stderr',
                        'filters' => array(
                            'priority' => array(
                                'name' => 'priority',
                                'options' => array(
                                    'priority' => \Zend\Log\Logger::DEBUG
                                )
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
