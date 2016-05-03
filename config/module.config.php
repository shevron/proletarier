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
                'proletarier-run-broker' => array(
                    'options' => array(
                        'route' => 'proletarier broker',
                        'defaults' => array(
                            'controller' => 'proletarier',
                            'action'     => 'run-broker'
                        )
                    )
                ),

                'proletarier-run-worker' => array(
                    'options' => array(
                        'route' => 'proletarier worker',
                        'defaults' => array(
                            'controller' => 'proletarier',
                            'action'     => 'run-worker'
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
            'Proletarier\Broker'       => Proletarier\Broker\Factory::class,
            'Proletarier\Worker'       => Proletarier\Worker\Factory::class,
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
