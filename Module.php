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

namespace Proletarier;

use Zend\Console\Adapter\AdapterInterface;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;

class Module implements AutoloaderProviderInterface, ConsoleUsageProviderInterface
{
    /**
     * Get configuration
     *
     * This also sets some dynamic default for the worker bind address, if none was set
     *
     * @return array
     */
    public function getConfig()
    {
        $config = require __DIR__ . '/config/module.config.php';
        if (! $config['proletarier']['worker']['bind']) {
            $config['proletarier']['worker']['bind'] = 'ipc://' . sys_get_temp_dir() . '/proletarier_ipc.sock';
        }

        return $config;
    }

    /**
     * Get autoloader configuration
     *
     * @return array
     */
    public function getAutoloaderConfig()
    {
        return [
            'Zend\Loader\ClassMapAutoloader' => [
                __DIR__ . '/autoload_classmap.php'
            ],
            'Zend\Loader\StandardAutoloader' => [
                'namespaces' => [
                    __NAMESPACE__ => __DIR__ . '/src/' . __NAMESPACE__
                ]
            ],
        ];
    }

    /**
     * Returns an array or a string containing usage information for this module's Console commands.
     * The method is called with active Zend\Console\Adapter\AdapterInterface that can be used to directly access
     * Console and send output.
     *
     * If the result is a string it will be shown directly in the console window.
     * If the result is an array, its contents will be formatted to console window width. The array must
     * have the following format:
     *
     *     return array(
     *                'Usage information line that should be shown as-is',
     *                'Another line of usage info',
     *
     *                '--parameter'        =>   'A short description of that parameter',
     *                '-another-parameter' =>   'A short description of another parameter',
     *                ...
     *            )
     *
     * @param AdapterInterface $console
     *
     * @return array|string|null
     */
    public function getConsoleUsage(AdapterInterface $console)
    {
        return array(
            'proletarier broker' =>
                'Run the proletarier message broker',
            'proletarier worker' =>
                'Run a Proletarier worker process',
            'proletarier trigger <event> [<params>]' =>
                'Trigger an event, with optional parameters as a JSON-seiralized string'
        );
    }
}
