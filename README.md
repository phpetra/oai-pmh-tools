oai-pmh-tools - PHP Tools for OAI-PMH
=======================================

The OaiPmhTools provides a set of classes for both Harvesting data sets from OAI-PMH servers and creating such a server.
These tools are framework independent and require nothing more than either lib_xml or curl to be enabled.

For more information on the Archives Initiative Protocol for Metadata Harvesting, please see
[[the specification on the openarchives.org website](http://www.openarchives.org/OAI/openarchivesprotocol.html)]

This set of tools include:
- An OaiPmhClient that sends requests to the external webservice.
- A Harvester - first feature after, once client is finished
- @todo build the Discovery / Explorer
- @todo build the server side of things

`Work in progress!!`

Installation
-------------

Simplest is to add the following to `composer.json`:
Although the package is not uploaded yet

```bash
{
    "require": {
        "phpetra/oai-pmh-tools": "dev-master"
    }
}
```

And then run:

```bash
php composer.phar install
```

Usage
-----

### The OaiPmhClient

In order to remain as flexible as possible, the OaiPmhClient comes with two Adapters:
- One adapter using xml
- And another one that uses curl to do the remote calls

Both use DOM to parse the xml.

Setting up the client is as simple as:

```php

use OaiPmhTools\Client\XmlClient;

$client = new XmlClient();

```

### The Harvester

For harvesting data from an OAI-PMH server where you know exactly what you want to retrieve, the configuration and setup is pretty simple:
First step is to configure some things:

- the Harveste can use a mapper and a database...
@todo complete this

