Overview
=========
Proletarier is a [ZeroMQ](http://zeromq.org) based background job processing
module for [Zend Framework 2](http://framework.zend.com) applications. It is
designed to provide a simplified, ZF2 compatible interface for concurrent job
processing over the ZMQ extension API.

Proletarier's approach is to provide a lightweight framework for creating jobs
in a way that will allow maximum re-use of existing ZF2 application code. The
general idea is that jobs are represented by callbacks and are mapped using
routes. Access to the ZF2 ServiceManager, and in turn to existing model and
helper classes, should be simple. Additionally, the ZF2 EventManager is used
wherever possible to allow event-based integration and extension points.

Requirements
============
Software dependencies are defined in the `composer.json` file. Less formally,
these include:

* PHP >= 5.4
* The `zmq` PHP extension >= 1.1
* A recent version of Zend Framework 2.x
