<?php

/**
 * Shoppimon Proletarier - Async event handler for ZF2 apps
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar@shoppimon.com
 */

namespace Proletarier\Client;

use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class Factory implements FactoryInterface
{
    /**
     * Create service
     *
     * @param ServiceLocatorInterface $serviceLocator
     *
     * @return mixed
     */
    public function createService(ServiceLocatorInterface $serviceLocator)
    {
        return self::factory($serviceLocator);
    }

    /**
     * @param ServiceLocatorInterface $locator
     *
     * @return Client
     * @throws \ErrorException
     */
    public static function factory(ServiceLocatorInterface $locator)
    {
        $config = $locator->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration is missing the 'proletarier' key");
        }
        $config = $config['proletarier'];

        $connect = $config['client']['connect'];
        if (! $connect) {
            // Fall back: connect to the broker bind address
            $connect = $config['broker']['bind'];
        }

        if ($config['client']['mock']) {
            $client = new MockClient($connect);
        } else {
            $client = new Client($connect);
        }

        return $client;
    }
}
