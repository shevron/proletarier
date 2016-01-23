<?php

/**
 * Proletarier Module Configuration File
 */

return array(

    'proletarier' => array(
        'listeners' => array(),

        'client' => array(
            'connect' => null,
            'mock'    => false,
        ),

        'broker' => array(
            'bind'    => 'tcp://127.0.0.1:9105',
        ),

        'worker' => array(
            'pool_size' => 5,
            'bind'      => null,
            'connect'   => null,
        ),
    ),

    'console' => array(
        'router' => array(
            'routes' => array(
                'proletarier-run' => array(
                    'options' => array(
                        'route' => 'proletarier run',
                        'defaults' => array(
                            'controller' => 'proletarier',
                            'action'     => 'run'
                        )
                    )
                ),

                'proletarier-trigger' => array(
                    'options' => array(
                        'route' => 'proletarier trigger <event> [<params>]',
                        'defaults' => array(
                            'controller' => 'proletarier',
                            'action'     => 'trigger'
                        )
                    )
                ),
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
            'Proletarier\Worker'       => Proletarier\Worker\Factory::class,
            'Proletarier\WorkerPool'   => array('Proletarier\Worker\WorkerPool', 'factory'),
            'Proletarier\Client'       => array('Proletarier\Client\Factory', 'factory'),
            'Proletarier\EventManager' => 'Zend\Mvc\Service\EventManagerFactory',
        ),

        'abstract_factories' => array(
            'Proletarier\Log'       => 'Zend\Log\LoggerAbstractServiceFactory',
        ),

        'invokables' => array(
            'Proletarier\Handler\EventLogger' => 'Proletarier\Handler\EventLogger',
        ),
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
                                    'priority' => \Zend\Log\Logger::INFO
                                )
                            ),
                        ),
                    ),
                ),
            ),
        ),
    ),
);
