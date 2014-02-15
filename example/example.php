<?php
/**
 * User: @PHPetra
 *
 * Example usage of the OAI-PMH client
 *
 */
require __DIR__  . '/../vendor/autoload.php';

use OaiPmhTools\Client\XmlClient;

$uri = 'http://data.beeldengeluid.nl/oai-pmh';
$client = new XmlClient();

$client
    ->setUri($uri)
    ->setMetadataPrefix('oai_dc')
    ->listRecords()
;

$response = $client->getResponse();

var_dump($response);