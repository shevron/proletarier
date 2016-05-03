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
