<?php

namespace Proletarier;

use Zend\Console\Adapter\AdapterInterface;
use Zend\EventManager\Event;
use Zend\EventManager\EventManager;
use Zend\ModuleManager\Feature\AutoloaderProviderInterface;
use Zend\ModuleManager\Feature\ConsoleUsageProviderInterface;
use Zend\ServiceManager\ServiceManager;

class Module implements AutoloaderProviderInterface, ConsoleUsageProviderInterface
{
    public function onBootstrap(Event $e)
    {
        /* @var $serviceManager ServiceManager */
        $serviceManager = $e->getTarget()->getServiceManager();
        $eventManager = $serviceManager->get('Proletarier\EventManager'); /* @var $eventManager EventManager */

        $eventManager->attach(new LoggingListener($serviceManager->get('Proletarier\Log')));

        // If we can (PHP 5.5 +), set the process title of workers after they launch
        if (function_exists('cli_set_process_title')) {
            $eventManager->attach('workerpoo.launch', function ($e) {
                cli_set_process_title('Proletarier master');
            });

            $eventManager->attach('worker.launch', function ($e) {
                cli_set_process_title('Proletarier worker');
            });
        }
    }

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
            $config['proletarier']['worker']['bind'] = 'ipc://' . sys_get_temp_dir() . '/proletarier_ipc_' .
                md5(microtime() . rand(0, PHP_INT_MAX)) . '.sock';
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
            'proletarier run' =>
                'Run the Proletarier message broker',
            'proletarier trigger <event> [<params>]' =>
                'Trigger an event, with optional parameters as a JSON-seiralized string'
        );
    }
}
