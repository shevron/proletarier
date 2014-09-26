<?php

namespace Proletarier\Client;

class MockClient extends Client
{
    protected $captureMessages = false;

    protected $capturedMessages = array();

    /**
     * Enable or disable message capturing
     *
     * @param bool $capture
     */
    public function captureMessages($capture = true)
    {
        $this->captureMessages = (bool) $capture;
    }

    /**
     * Get all captured messages
     *
     * For messages to be captured, you must call captureMessages() first.
     *
     * @return array
     */
    public function getCapturedMessages()
    {
        return $this->capturedMessages;
    }

    /**
     * Mock the "send" method to do nothing
     *
     * @param string $message
     */
    protected function send($message)
    {
        if ($this->captureMessages) {
            $this->capturedMessages[] = $message;
        }
    }
}
