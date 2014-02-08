<?php
/**
 * @author PHPetra
 * Date: 1/31/14
 * Time: 10:19 PM
 *
 */

namespace OaiPmhTools\Client;

use OaiPmhTools\OaiServerException;

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
    protected $metadataPrefix = 'oai_dc';

    /** @var  array Simple array to hold the required response elements */
    protected $response;

    protected $rawResponse; // might not need this
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

    /** @var array The possible errors and exceptions, per verb */
    protected $requiredResponseElements = array(
        AbstractAdapter::VERB_IDENTIFY                 => array('repositoryName', 'baseURL', 'protocolVersion', 'earliestDatestamp', 'deletedRecord', 'granularity'),
        AbstractAdapter::VERB_LIST_METADATA_FORMATS    => array('metadataFormat'),
        AbstractAdapter::VERB_LIST_SETS                => array('badArgument', 'badResumptionToken', 'noSetHierarchy'),
        AbstractAdapter::VERB_LIST_IDENTIFIERS         => array('badArgument', 'badResumptionToken', 'cannotDisseminateFormat', 'noRecordsMatch', 'noSetHierarchy'),
        AbstractAdapter::VERB_LIST_RECORDS             => array('badArgument', 'badResumptionToken', 'cannotDisseminateFormat', 'noRecordsMatch', 'noSetHierarchy'),
        AbstractAdapter::VERB_GET_RECORD               => array('badArgument', 'cannotDisseminateFormat', 'idDoesNotExist'),
    );

    /**
     * Retrieve information about a repository
     */
    public function identify()
    {
        $params = array('verb' => self::VERB_IDENTIFY);
        $uri = $this->getUri() . http_build_query($params);

        $this->receive(self::VERB_IDENTIFY, $this->load($uri));
    }


    public function listSets()
    {
        $params = array('verb' => self::VERB_LIST_SETS);
        $uri = $this->getUri() . http_build_query($params);

        $dom = $this->load($uri);
        $sets = $dom->getElementsByTagName('set');

        $data = array();
        foreach ($sets as $set) {
            $spec['setSpec'] = $set->getElementsByTagname('setSpec')->item(0)->nodeValue;
            $spec['setName'] = $set->getElementsByTagname('setName')->item(0)->nodeValue;
            if ($set->getElementsByTagname('setDescription')->length > 0) {
                $spec['setDescription'] = $set->getElementsByTagname('setDescription')->item(0)->nodeValue;
            }

            $data['set'][] = $spec;
        }

        $this->response[self::VERB_LIST_SETS] = $data;
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

        $dom = $this->load($uri);

        // extract metadata information
        $metadataFormats = $dom->getElementsByTagName('metadataFormat');
        $data = array();
        foreach ($metadataFormats as $metaFormat) {
            $format['metadataPrefix'] = $metaFormat->getElementsByTagname('metadataPrefix')->item(0)->nodeValue;
            $format['schema'] = $metaFormat->getElementsByTagname('schema')->item(0)->nodeValue;
            $format['metadataNamespace'] = $metaFormat->getElementsByTagname('metadataNamespace')->item(0)->nodeValue;

            $data['metadataFormat'][] = $format;
        }

        $this->response[self::VERB_LIST_METADATA_FORMATS] = $data;
    }

    /**
     * Receives the required elements out of the response, as set in the requiredResponseElements
     *
     * @param $verb
     * @param $dom
     */
    protected function receive($verb, $dom)
    {
        $responseElements = $this->requiredResponseElements[$verb];
        $data = array();
        foreach ($responseElements as $element) {
            $data[$element] = $dom->getElementsByTagName($element)->item(0)->nodeValue;
        }

        $this->response[$verb] = $data;
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

} 