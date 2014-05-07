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
            'Proletarier\Broker'     => array('Proletarier\Broker', 'factory'),
        ),
    ),

    'proletarier' => array(
        'event_handlers' => array(
            array('*', array('Proletarier\Handler\Default', 'log')),
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
);
