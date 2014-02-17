<?php
/**
 * User: @PHPetra
 *
 * Example usage of the OAI-PMH client
 *
 */
require __DIR__  . '/../vendor/autoload.php';

use OaiPmhTools\Client\XmlClient;

// Listing Identifiers
$uri = 'http://data.beeldengeluid.nl/oai-pmh';
$client = new XmlClient();
$client
    ->setUri($uri)
    ->setMetadataPrefix('oai_dc')
    ->listIdentifiers()
;
var_dump($client->getResponse()['ListIdentifiers']);

/* Fetch available metadata formats
$uri = 'http://data.beeldengeluid.nl/oai-pmh';
$client = new XmlClient();
$client
    ->setUri($uri)
    ->listSets()
;
// see output on screen
*/

/* List Records form external repo with oai_dc metadata prefix
$uri = 'http://data.beeldengeluid.nl/oai-pmh';
$client = new XmlClient();
$client
    ->setUri($uri)
    ->setMetadataPrefix('oai_dc')
    ->listRecords()
;
var_dump($client->getResponse()['ListRecords']);
*/

