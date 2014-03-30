<?php

namespace Proletarier\Message;

interface MessageInterface extends \Zend\Stdlib\MessageInterface
{
    /**
     * Serialize this message into a string
     *
     * @return string
     */
    public function serialize();

    /**
     * Load a serialized string into this message object
     *
     * @param string $serialized
     *
     * @return MessageInterface
     */
    public function unserialize($serialized);
}
