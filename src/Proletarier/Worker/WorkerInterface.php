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

namespace Proletarier\Worker;

use Zend\EventManager\EventManagerAwareInterface;

interface WorkerInterface extends EventManagerAwareInterface
{
    /**
     * Start the worker. In most cases this would fork out a process and return the process ID
     *
     * @return integer
     */
    public function launch();

    /**
     * Shut the worker down.
     *
     * @return mixed
     */
    public function shutdown();
}
