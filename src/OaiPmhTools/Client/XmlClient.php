<?php
/**
 * @author PHPetra
 * Date: 1/31/14
 * Time: 10:11 PM
 * 
 */

namespace OaiPmhTools\Client;

use DOMDocument;
use OaiPmhTools\OaiServerException;

class XmlClient extends AbstractAdapter {

    /**
     * Loads the xml document from the OAI-PMH server
     * Checks for possible errors and returns the result
     */
    protected function load($uri)
    {
        // ensure we open a correct stream context
        $context = stream_context_create(array(
            'http' => array(
                'user_agent' => 'PHP libxml agent',
            )
        ));
        libxml_set_streams_context($context);

        $doc = @DOMDocument::load($uri);
        if (!$doc) {
            throw new OaiServerException('Failed to load xml from the server.');
        }

        // server returned an error
        $error = $doc->getElementsByTagName('error');
        if ($error->length > 0) {
            $this->handleServerError($error);
        }

        return $doc;
    }


    public function listRecords()
    {
        if ($this->getResumptionToken()) {

        }

    }

    public function listSets()
    {

    }

    public function listIdentifiers()
    {

    }

    public function getRecord($identifier)
    {

    }


} 