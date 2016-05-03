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
                array('account.created', 'Application\EventHandler\AccountCreationHandler'),
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

A Note on Service Persistance
-----------------------------
Unlike standard Web PHP processes, Proletarier processes are long-running and
do not automatically clean up resources on request end. This can have some
surprising side effects on resource availability and memory utilization, and
you should keep this in mind, especially when using persistant services such
as ServiceManager or DI based services.

Most notably, services that maintain network or DB connections may need some
adjustments when being used inside a Proletarier event handler.

For example, many Zend\Db adapters will not close connections unless explicitly
ordered to, or unless the connection object is destroyed. However, when fetched
as a non-shared service from Zend\ServiceManager a DB adapter object will never
be explicitly destroyed as it is cached by the service manager itself. For this
reason, and for resource management reasons, it is recommended to explicitly
shut down connections when they are no longer used inside a handler:

    namespace Application\EventHandler;

    use Proletarier\Handler\AbstractHandler;
    use Zend\EventManager\EventInterface;

    class AccountCreationHandler extends AbstractHandler
    {
        /**
         * Do some post account creation processing
         *
         * @param EventInterface $event
         * @return bool
         */
        public function __invoke(EventInterface $event)
        {
            $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

            // ... do some stuff with the DB adapter

            // Disconnect form DB to avoid timeout errors on long-running process
            $dbAdapter->getDriver()->getConnection()->disconnect();
        }
    }

Another notable example are network based Zend\Mail transports, such as
`Zend\Mail\Transport\Smtp`. These do not provide an API to explicitly close
connections, and thus it is recommended to use non-shared instances of them
if fetched from the service manager:

    namespace Application\EventHandler;

    use Zend\EventManager\EventInterface;
    use Zend\EventManager\EventManagerInterface;
    use Zend\EventManager\ListenerAggregateInterface;
    use Zend\EventManager\ListenerAggregateTrait;
    use Zend\ServiceManager\ServiceLocatorAwareInterface;
    use Zend\ServiceManager\ServiceLocatorAwareTrait;

    class TeamNotificationListener implements ListenerAggregateInterface, ServiceLocatorAwareInterface
    {
        use ListenerAggregateTrait;
        use ServiceLocatorAwareTrait;

        /**
         * Attach listeners for internal notifications on events
         *
         * @param EventManagerInterface $events
         */
        public function attach(EventManagerInterface $events)
        {
            $this->listeners[] = $events->attach('account.created', array($this, 'accountCreated'));
            $this->listeners[] = $events->attach('account.expired', array($this, 'accountExpired'));
        }

        public function accountCreated(EventInterface $event)
        {
            $this->sendNotification(
                "An account was created",
                "Account ID is " . $event->getParam('account_id')
            );
        }

        public function accountExpired(EventInterface $event)
        {
            $this->sendNotification(
                "An account has expired",
                "Account ID is " . $event->getParam('account_id')
            );
        }

        private function sendNotification($subject, $message)
        {
            $config = $this->getServiceLocator()->get('Config')['notifications'];
            $to   = $config['to'];
            $from = $config['from'];

            // Need a new instance each time due to long-running process effects
            // (must disconnect and reconnect)
            $this->getServiceLocator()->setShared('MailTransport', false);
            /* @var $transport \Zend\Mail\Transport\TransportInterface */
            $transport = $this->getServiceLocator()->get('MailTransport');

            $mail = new Message();
            $mail->setTo($to)
                 ->setFrom($from)
                 ->setSubject($subject)
                 ->setBody($message);

            $transport->send($mail);
        }
    }

Note that in this case, in the `sendNotification` method, the `MailTransport`
service is marked as non-shared before it is fetched, to ensure a new
transport object is created on each call (and is destroyed at the end of it).

The above code is also a good example of using a ListenerAggregate object to
handle multiple events with some contained code.

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

TODO
====
Some considerations and ideas for future improvements:

* Allow easy hooking into internal events for things like resource cleanup,
  connection closing, etc. after an event was handled
* Better internal logging
* Delayed event processing using persistant queue-like storage
* Crash detection, process recycling (good for resource consumption)
* Parallel execution of event handlers (?)
* Auto scale-up / scale-down

Copyright
=========
Proletarier is (c) Shoppimon LTD and is released under the terms of the
Apache 2.0 License. See LICENSE for details.
