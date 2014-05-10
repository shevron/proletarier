<?php

namespace Proletarier;

use Zend\EventManager\EventInterface;

class Event extends \Zend\EventManager\Event
{
    /**
     * Convert a JSON-serialized event into an object
     *
     * @param $string
     *
     * @return Event
     * @throws \InvalidArgumentException
     */
    public static function fromJson($string)
    {
        $data = json_decode($string, true);
        if (! is_array($data)) {
            throw new \InvalidArgumentException("invalid payload, not a JSON-serialized object");
        }

        if (! isset($data['name'])) {
            throw new \InvalidArgumentException("name attribute is missing from json-decoded string");
        }

        $event = new Event($data['name']);
        if (isset($data['params'])) {
            $event->setParams($data['params']);
        }

        return $event;
    }

    /**
     * Convert an event object into a serialized JSON string
     *
     * @param EventInterface $event
     *
     * @return mixed|string|void
     */
    public static function toJson(EventInterface $event)
    {
        return json_encode(array(
            'name' => $event->getName(),
            'params' => $event->getParams()
        ));
    }
}
