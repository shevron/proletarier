<?php

namespace Proletarier\Message;

interface RequestInterface extends \Zend\Stdlib\RequestInterface
{
    public function setAction($action);

    public function getAction();

    public function toString();

    static public function fromString($string);
}
