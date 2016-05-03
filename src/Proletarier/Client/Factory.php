<?php

/**
 *    Copyright 2016 Shahar Evron
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
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
