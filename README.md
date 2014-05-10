An asynchronous event handler module for Zend Framework 2 apps based on ØMQ

Overview
=========
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
============
Software dependencies are defined in the `composer.json` file. Less formally,
these include:

* PHP >= 5.4
* The `zmq` PHP extension >= 1.1
* A recent version of Zend Framework 2.x (tested with 2.3)
