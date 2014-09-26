<?php

namespace Proletarier\Client;

/**
 * Proletarier Client Interface
 *
 * @package Proletarier\Client
 */
interface ClientInterface
{
    public function trigger($event, array $params = array());
}
