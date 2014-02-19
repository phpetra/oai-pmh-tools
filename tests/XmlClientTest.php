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

        $this->assertArrayHasKey('repositoryName', $response);
        $this->assertArrayHasKey('baseURL', $response);
        // we could test all required elements but might not be needed
    }

    public function testRetrievingMetadataFormats()
    {
        $this->client
            ->setUri($this->testRepo)
            ->listMetadataFormats();
        $response = $this->client->getResponse();

        $this->assertArrayHasKey('metadataFormat', $response);
        $this->assertArrayHasKey('metadataPrefix', $response['metadataFormat'][0]);
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

        $this->assertArrayHasKey('set', $response);
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
        $this->assertGreaterThan(1, count($response));
        $this->assertArrayHasKey('identifier', $response[0]);
        $this->assertArrayHasKey('timestamp', $response[0]);
        $this->assertArrayHasKey('metadata', $response[0]);
    }

    public function testCanListIdentifiers()
    {
        $uri = 'http://data.beeldengeluid.nl/oai-pmh';
        $this->client
            ->setUri($uri)
            ->setMetadataPrefix('oai_dc')
            ->listIdentifiers(1)
        ;

        $response = $this->client->getResponse();
        $this->assertGreaterThan(1, count($response));
        $this->assertArrayHasKey('identifier', $response[0]);
    }

}
