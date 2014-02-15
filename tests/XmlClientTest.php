<?php
/**
 * @author PHPetra
 * 
 */

namespace OaiPmhTest;

use OaiPmhTools\Client\AbstractAdapter;
use OaiPmhTools\Client\XmlClient;
use OaiPmhTools\RuntimeException;

/**
 * Testing a simple setup
 * Need a live Internet connection to be able to do this
 * Should maybe replace with @fixtures?
 *
 */
class XmlClientTest extends \PHPUnit_Framework_TestCase
{

    protected $testRepo = 'http://www.avhumboldt.net/oai/oai.php';

    /** @var \OaiPmhTools\Client\XmlClient */
    protected $client;

    public function setUp()
    {
        $this->client = new XmlClient();
    }

    public function testCanInitialize()
    {
        $this->assertInstanceOf('OaiPmhTools\Client\XmlClient', $this->client);
    }

    public function testCanSetUri()
    {
        $this->client->setUri($this->testRepo);
        $this->assertEquals($this->testRepo . '?', $this->client->getUri());
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionCode 101
     */
    public function testNotSettingUriReturnsException()
    {
        $this->client->identify();
    }

    /** Ensure something works */
    public function testCanCallIdentify()
    {
        $this->client
            ->setUri($this->testRepo)
            ->identify();
        $response = $this->client->getResponse();

        $this->assertArrayHasKey('repositoryName', $response[AbstractAdapter::VERB_IDENTIFY]);
        $this->assertArrayHasKey('baseURL', $response[AbstractAdapter::VERB_IDENTIFY]);
        // we could test all required elements but might not be needed
    }

    public function testRetrievingMetadataFormats()
    {
        $this->client
            ->setUri($this->testRepo)
            ->listMetadataFormats();
        $response = $this->client->getResponse();

        $this->assertArrayHasKey('metadataFormat', $response[AbstractAdapter::VERB_LIST_METADATA_FORMATS]);
        $this->assertArrayHasKey('metadataPrefix', $response[AbstractAdapter::VERB_LIST_METADATA_FORMATS]['metadataFormat'][0]);
    }

    /**
     * @expectedException RuntimeException
     * @expectedExceptionMessage noSetHierarchy
     */
    public function testListSetsWithoutSetsThrowsException()
    {
        $this->client
            ->setUri($this->testRepo)
            ->listSets();
        $response = $this->client->getResponse();
    }

    public function testCanListSets()
    {
        $uri = 'http://data.beeldengeluid.nl/oai-pmh';
        $this->client
            ->setUri($uri)
            ->listSets();
        $response = $this->client->getResponse();

        $this->assertArrayHasKey(AbstractAdapter::VERB_LIST_SETS, $response);
    }

    /**
    * @expectedException RuntimeException
    * @expectedExceptionMessage requires a metadataPrefix
    */
    public function testListRecordsWithoutMetdataPrefixThrowsException()
    {
        $this->client
            ->setUri($this->testRepo)
            ->listRecords();
        $response = $this->client->getResponse();
    }

    public function testCanListRecords()
    {
        $uri = 'http://data.beeldengeluid.nl/oai-pmh';
        $this->client
            ->setUri($uri)
            ->setMetadataPrefix('oai_dc')
            ->listRecords()
        ;

        $response = $this->client->getResponse();

        $this->assertContains('data', $response);
    }

}
