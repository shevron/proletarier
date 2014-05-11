Proletarier
===========
An asynchronous event handler module for Zend Framework 2 apps based on ØMQ

Overview
--------
Proletarier is a [ZeroMQ](http://zeromq.org) based asynchronous event processing
module for [Zend Framework 2](http://framework.zend.com) applications. It is
designed to provide a simplified, ZF2 compatible interface for background
processing over the ZMQ extension API.

Proletarier's approach is to provide a lightweight framework for asynchronous
event handling in a way that will allow maximum re-use of existing ZF2 application
code. The API largely follows ZF2's EventManager API - you register event
handlers, launch the background processing daemon and then trigger events from
your application using the Proletarier client. Unlike a ZF2 EventManager built
in to your main Web app code, event handling happens asynchronously, in the
background.

Event handlers are simple callables or invokable objects. Access to the ZF2
ServiceManager, and in turn to existing model and helper classes, should be simple.

The background processing daemon is launched as a ZF2 Console action, and will
in turn fork out multiple processes who will wait for events. This allows for fast
event handling without any real-time overhead of loading configuration and
bootstrapping.

Requirements
------------
Software dependencies are defined in the `composer.json` file. Less formally,
these include:

* PHP >= 5.4
* The `zmq` PHP extension >= 1.1
* A recent version of Zend Framework 2.x (tested with 2.3)

Installation
------------
You can install Proletarier via composer (currently by pulling from github) by
adding the following lines to your composer.json:

    "require": {
        "shevron/proletarier": "dev-master"
    },
    "repositories": [
        {
            "type": "vcs",
            "url": "git@github.com:shevron/proletarier.git"
        }
    ]

Then, add Proletarier to your application's `config/application.config.php`
modules list:

    'modules' => array(
        'Application',
        'Proletarier',
    ),

This is enough to provide Proletarier services, but in order to use Proletarier
you will also need to create and register some event handlers. See "Integration"
below for some guidelines.

Running the Processing Daemon
-----------------------------
The Proletarier daemon / service is executed via CLI PHP and is implemented as
a ZF Console action. You can run it from the command line, from the project
directory:

    $ php public/index.php proletarier run

The daemon will fork out several worker processes - the number of which is
configurable and should be determined based on load / machine resources.

**Note**: by default, the Proletarier daemon uses a unix domain socket to
communicate with its workers, and the socket is created in the system's
temporary directory. You need to make sure this directory is writable by
the user running Proletarier, or change the configuration to use a different
socket file or switch to TCP-based communication.

Shutting the daemon down can be down by hitting Ctrl-C or sending the TERM
(15) signal to the parent process.

Integrating Proletarier with your app
=====================================

Basic Configuration
-------------------
After installing and enabling Proletarier, it is recommended that you create
a dedicated configuration file for it in
`config/autoload/proletarier.config.global.php` and make app-global changes there.
You can also create a `.local.php` version of the file if you want to make
environment local changes to the configuration. Here is a sample configuration file:

    <?php

    return array(
        'proletarier' => array(
            'listeners' => array(
                array('*', 'Proletarier\Handler\EventLogger', -1000),
                array('account.created', 'Application\EventHandler\ConfigureWorkflows'),
                array('account.expired', array('Application\Model\Account', 'expiredHandler),
                'Application\EventHandler\TeamNotificationListener',
            ),
        ),
    );

The `['proletarier']['listeners']` array contains a list of event listeners
which are to be triggered by the processing daemon on specific events. Each
item in the list can be one of the following:

* An array, where the first item is an event name or a '*', and the second
item is a callable or the class name of an invokable object. You can optionally
specify the handler priority as a third item.
* The name of a class implementing the
`Zend\EventManager\ListenerAggregateInterface` interface. This allows multiple
listeners to be registered by implementing one class.

Event triggering is done through a `Zend\EventManager\EventManager` object.
Specifying '*' as the event name means the listener will be called for any
event. This is useful for logging purposes, and the `Proletarier\Handler\EventLogger`
handler is provided for this purpose, and as an example of how a handler can
be created.

Triggering Events from your Web Application
-------------------------------------------
Once your handlers are configured and the daemon is running, triggering
events from your code (usually from Controllers or Models) is very simple:

    public function createAction()
    {
        // ... validate account creation ...

        // ... when all is well and the account is created, trigger event

        /* @var $client \Proletarier\Client */
        $client = $this->getServiceLocator()->get('Proletarier\Client');
        $client->trigger('account.created', array('account' => $account));
    }

All you need is to obtain the `Proletarier\Client` object from the
ServiceManager.

The `trigger` method takes an event name, or an `EventInterface` object,
and an optional array of parameters that will be passed along with the
event. Note that all parameters must be JSON-serializable - it is recommended
that objects frequently passed as event parameters to Proletarier implement
the `\JsonSerializable` interface.

Once you call trigger, the backend daemon will recieve an event object
through a message and will trigger, one by one, all listeners attached
to this event.

Additional Configuration
------------------------
TBD. In the mean time look in the module's `config/module.config.php` file.
You can easily tweak the following through configuration:

* Network Configuration
* Resources and Timeouts
* Logging
* ...

Provided Services
-----------------

TBD.

* `Proletarier\Broker`
* `Proletarier\Worker`
* `Proletarier\WorkerPool`
* `Proletarier\Client`
* `Proletarier\EventManager`
* `Proletarier\Log`
* `Proletarier\Handler\EventLogger`

Internal Events
---------------
Proletarier has tight integration with the EventManager for internal events
(not your application events, but events that happen within Proletarier (such
as a new message arriving or internal errors). The following events can be
listened for if you want to extend Proletarier with additional logic:

TBD

Copyright
=========
Proletarier is (c) Shoppimon LTD and is released under the terms of the
Apache 2.0 License. See LICENSE for details.
