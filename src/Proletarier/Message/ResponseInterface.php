<?php

namespace Proletarier\Message;

interface ResponseInterface extends MessageInterface
{
    public function setStatus($status);

    public function getStatus();
}
