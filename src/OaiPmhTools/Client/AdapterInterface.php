<?php
/**
 * @author PHPetra
 * Date: 1/31/14
 * Time: 10:03 PM
 * 
 */

namespace OaiPmhTools\Client;

interface AdapterInterface {

    public function identify();
    public function listMetadataFormats($identifier);
    public function listSets();
    public function listIdentifiers();
    public function listRecords();
    public function getRecord($identifier);

}