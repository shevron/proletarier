<?php

/**
 * shoppimon-frontend
 *
 * @copyright (c) 2016 Shoppimon LTD
 * @author    shahar
 */

namespace Proletarier\Broker;

use Zend\ServiceManager\ServiceLocatorInterface;

class Factory
{
    /**
     * @param $services ServiceLocatorInterface
     * @return Broker
     * @throws \ErrorException
     */
    public function __invoke($services)
    {
        $config = $services->get('Config');
        if (! isset($config['proletarier'])) {
            throw new \ErrorException("Configuration is missing the 'proletarier' key");
        }
        $config = $config['proletarier'];

        /* @var $events \Zend\EventManager\EventManagerInterface */
        $events = $services->get('Proletarier\EventManager');
        $broker = new Broker($config['broker']['bind'], $config['worker']['bind']);
        $broker->setEventManager($events);

        return $broker;
    }
}
