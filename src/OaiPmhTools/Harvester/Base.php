<?php
/**
 * User: @PHPetra
 * Date: 2/19/14
 * 
 */

namespace OaiPmhTools\Harvester;
use OaiPmhTools\Client\AbstractAdapter;

/**
 * Class Base
 *
 * Base class for the Harvester
 * It applies a mapper and some other stuff like a storage to the OAI-PMH client to make discovering a dataset and mapping it to a database even easier.
 *
 * @package OaiPmhTools\Harvester
 */
class Base {

    /** @var  AbstractAdapter */
    protected $client;

    protected $mapper;

    public function __construct($client, $mapper = null)
    {
        $this->client = $client;
        if (null !== $mapper) {
            $this->mapper = $mapper;
        }
    }

    public function harvestIdentifiers()
    {

    }

    public function harvestData()
    {

        $uri = 'http://data.beeldengeluid.nl/oai-pmh';
        $this->client
            ->setUri($uri)
            ->setMetadataPrefix('oai_dc')
            ->listRecords();
        $records = $this->client->getResponse();
        if (count($records) > 0) {
            foreach ($records as $record) {
                // TODO implement this..
            }
        }
    }

    public function applyMapping()
    {

    }

} 