<?php
/**
 * @author PHPetra
 * Date: 1/31/14
 * Time: 10:19 PM
 *
 */

namespace OaiPmhTools\Client;

use OaiPmhTools\OaiServerException;
use OaiPmhTools\RuntimeException;
use OaiPmhTools\Client\AdapterInterface;

abstract class AbstractAdapter implements AdapterInterface {

    /** This verb is used to retrieve information about a repository. */
    const VERB_IDENTIFY = 'Identify';

    /** This verb is used to retrieve the metadata formats available from a repository. */
    const VERB_LIST_METADATA_FORMATS = 'ListMetadataFormats';

    /** This verb is used to retrieve the set structure of a repository, useful for selective harvesting. */
    const VERB_LIST_SETS = 'ListSets';

    /** This verb is an abbreviated form of ListRecords, retrieving only headers rather than records. */
    const VERB_LIST_IDENTIFIERS = 'ListIdentifiers';

    /** This verb is used to harvest records from a repository. Optional arguments permit selective harvesting of records based on set membership and/or datestamp.  */
    const VERB_LIST_RECORDS = 'ListRecords';

    /** This verb is used to retrieve an individual metadata record from a repository. */
    const VERB_GET_RECORD = 'GetRecord';

    /** @var  string The base uri for the OAI-PMH service */
    protected $uri;

    /** @var  string The resumptionToken for the OAI-PMH service */
    protected $resumptionToken;

    /** @var  string The oai set to request */
    protected $set;

    /** @var  string The metadataPrefix parameter for use in the request uri */
    protected $metadataPrefix = null;

    /** @var  array Simple array to hold the required response elements */
    protected $response;

    protected $rawResponse; // todo also set RawResponse
    /**
     * @see http://www.openarchives.org/OAI/openarchivesprotocol.html#ListRecords
     * @var array Allowed request Params per verb
     */
    protected $allowedRequestParams = array(
        AbstractAdapter::VERB_IDENTIFY                 => null,
        AbstractAdapter::VERB_LIST_METADATA_FORMATS    => array('identifier'), //= optional
        AbstractAdapter::VERB_LIST_SETS                => array('resumptionToken'),
        AbstractAdapter::VERB_LIST_IDENTIFIERS         => array('resumptionToken', 'metadataPrefix', 'set', 'from', 'until'),
        AbstractAdapter::VERB_LIST_RECORDS             => array('resumptionToken', 'metadataPrefix', 'set', 'from', 'until'), // metadataPrefix is required, except when resumptionToken is set (exclusive argument)
        AbstractAdapter::VERB_GET_RECORD               => array('identifier', 'metadataPrefix'), // both required
    );

    // TODO implement from and until

    /**
     * Retrieve information about a repository
     */
    public function identify()
    {
        $params = array('verb' => self::VERB_IDENTIFY);
        $uri = $this->getUri() . http_build_query($params);

        $this->writeMsg('Calling Identify');
        $doc = $this->load($uri);

        $responseElements = array('repositoryName', 'baseURL', 'protocolVersion', 'earliestDatestamp', 'deletedRecord', 'granularity');
        $data = array();
        foreach ($responseElements as $element) {
            $data[$element] = $doc->getElementsByTagName($element)->item(0)->nodeValue;
        }

        $this->response = $data;
    }


    public function listSets()
    {
        $params = array('verb' => self::VERB_LIST_SETS);
        $uri = $this->getUri() . http_build_query($params);

        $this->writeMsg('Fetching sets');
        $doc = $this->load($uri);
        $sets = $doc->getElementsByTagName('set');

        $data = array();
        foreach ($sets as $set) {
            $spec['setSpec'] = $set->getElementsByTagname('setSpec')->item(0)->nodeValue;
            $spec['setName'] = $set->getElementsByTagname('setName')->item(0)->nodeValue;
            if ($set->getElementsByTagname('setDescription')->length > 0) {
                $spec['setDescription'] = $set->getElementsByTagname('setDescription')->item(0)->nodeValue;
            }

            $data['set'][] = $spec;
            $this->writeMsg("Found available set '{$spec['setSpec']}'.");
        }

        if (empty($data)) {
            $this->writeMsg('No sets found');
        }

        $this->response = $data;
    }

    /**
     * List the available metadata formats for the repo
     * If identifier is set it only returns the format for one particular item
     *
     * @param null $identifier
     */
    public function listMetadataFormats($identifier = null)
    {
        $params = array('verb' => self::VERB_LIST_METADATA_FORMATS);
        if (null !== $identifier) {
            $params['identifier'] = $identifier;
        }

        $uri = $this->getUri() . http_build_query($params);

        $this->writeMsg('Fetching metadata formats'); // todo set verbose flag
        $doc = $this->load($uri);

        // extract metadata information
        $metadataFormats = $doc->getElementsByTagName('metadataFormat');
        $data = array();
        foreach ($metadataFormats as $metaFormat) {
            $format['metadataPrefix'] = $metaFormat->getElementsByTagname('metadataPrefix')->item(0)->nodeValue;
            $format['schema'] = $metaFormat->getElementsByTagname('schema')->item(0)->nodeValue;
            $format['metadataNamespace'] = $metaFormat->getElementsByTagname('metadataNamespace')->item(0)->nodeValue;

            $data['metadataFormat'][] = $format;

            $this->writeMsg("Found available metadata format '{$format['metadataPrefix']}'.");
        }

        $this->response = $data;
    }

    public function listIdentifiers($limit = 1)
    {
        if ($this->getResumptionToken()) {
            $params = array(
                'verb' => self::VERB_LIST_IDENTIFIERS,
                'resumptionToken' => $this->getResumptionToken()
            );
            $uri = $this->getUri() . http_build_query($params);
        } else {
            if (!$this->getMetadataPrefix()) {
                throw new RuntimeException('ListIdentifiers requires a metadataPrefix. None was set.');
            }
            $params = array(
                'verb'      => self::VERB_LIST_IDENTIFIERS,
                'metadataPrefix' => $this->getMetadataPrefix()
            );
            $uri = $this->getUri() . http_build_query($params);
        }

        $loop = 1;
        while (true) {
            if ($loop > $limit) {
                $this->writeMsg("Quitting because the set limit of '{$limit}' was reached.");
                break;
            }
            // clear response from earlier requests
            $this->response = null;

            $this->writeMsg("Listing identifiers, loop {$loop}.");

            /** @var \DOMDocument $doc */
            $doc = $this->load($uri);

            $resumptionToken = $doc->getElementsByTagname('resumptionToken')->item(0);
            if (!$resumptionToken->nodeValue) {
                $this->writeMsg('No more receptionToken in server response. All done.');
                break;
            }

            $records = $doc->getElementsByTagname('header');
            foreach ($records as $record) {
                $identifier = $record->getElementsByTagname('identifier')->item(0)->nodeValue;
                $timestamp = $record->getElementsByTagname('datestamp')->item(0)->nodeValue;

                $data['identifier'] = $identifier;
                $data['timestamp'] = $timestamp;

                $this->response[] = $data;
            }
            $loop++;
        }
    }

    /**
     * List records form external resource
     * Using resumptionToken
     * It should be able to restart form a specific resumptionToken
     * TODO implement the restart from resumptionToken
     */
    public function listRecords($limit = 1)
    {
        if ($this->getResumptionToken()) {
            $params = array(
                'verb' => self::VERB_LIST_RECORDS,
                'resumptionToken' => $this->getResumptionToken()
            );
            $uri = $this->getUri() . http_build_query($params);
        } else {
            if (!$this->getMetadataPrefix()) {
                throw new RuntimeException('ListRecords requires a metadataPrefix. None was set.');
            }
            $params = array(
                'verb'      => self::VERB_LIST_RECORDS,
                'metadataPrefix' => $this->getMetadataPrefix()
            );
            $uri = $this->getUri() . http_build_query($params);
        }

        $loop = 1;
        while (true) {
            if ($loop > $limit) {
                $this->writeMsg("Quitting because the set limit of '{$limit}' was reached.");
                break;
            }
            // clear response from earlier requests
            $this->response = null;

            $this->writeMsg("Listing records, loop {$loop}.");

            /** @var \DOMDocument $doc */
            $doc = $this->load($uri);

            $resumptionToken = $doc->getElementsByTagname('resumptionToken')->item(0);
            if (!$resumptionToken->nodeValue) {
                $this->writeMsg('No more receptionToken in server response. All done.');
                break;
            }

            $records = $doc->getElementsByTagname('record');
            foreach ($records as $record) {
                $identifier = $record->getElementsByTagname('identifier')->item(0)->nodeValue;
                $timestamp = $record->getElementsByTagname('datestamp')->item(0)->nodeValue;

                $metadata = $record->getElementsByTagname('metadata')->item(0);

                $data['identifier'] = $identifier;
                $data['timestamp'] = $timestamp;
                $data['metadata'] = $metadata;

                $this->response[] = $data;
            }
            $loop++;
        }
    }

    /**
     * Generic way of handling the errors returned by the OAI-PMH server
     * @param $error \DOMElement
     * @throws \OaiPmhTools\OaiServerException
     * @return bool
     */
    protected function handleServerError($error)
    {
        $code = $error->item(0)->getAttribute('code');
        $message = $error->item(0)->nodeValue;

        throw new OaiServerException("The server returned an error: {$code}, with message: {$message}.");
    }

    /**
     * Returns the base URI used for calls with resumptionToken
     * @throws \OaiPmhTools\OaiServerException
     * @return string
     */
    public function getUri()
    {
        if (null === $this->uri) {
            throw new OaiServerException('The uri of the external repository is not set.', 101);
        }
        return trim($this->uri, '/') . '?';
    }

    /**
     * @param string $uri
     * @return $this
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }

    /**
     * @param string $set
     * @return $this
     */
    public function setSet($set)
    {
        $this->set = $set;

        return $this;
    }

    /**
     * @return string
     */
    public function getSet()
    {
        return $this->set;
    }

    /**
     * @param string $metadataPrefix
     * @return $this
     */
    public function setMetadataPrefix($metadataPrefix)
    {
        $this->metadataPrefix = $metadataPrefix;

        return $this;
    }

    /**
     * @return string
     */
    public function getMetadataPrefix()
    {
        return $this->metadataPrefix;
    }

    /**
     * @param string $resumptionToken
     * @return $this
     */
    public function setResumptionToken($resumptionToken)
    {
        $this->resumptionToken = $resumptionToken;

        return $this;
    }

    /**
     * @return string
     */
    public function getResumptionToken()
    {
        return $this->resumptionToken;
    }

    /**
     * @return array
     */
    public function getResponse()
    {
        return $this->response;
    }

    protected function writeMsg($msg) {
        fwrite(STDOUT, $msg . PHP_EOL);
    }

} 