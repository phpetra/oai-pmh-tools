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
    public function listIdentifiers($limit);
    public function listRecords($limit);
    public function getRecord($identifier);

}