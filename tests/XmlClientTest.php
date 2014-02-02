<?php
/**
 * @author PHPetra
 * Date: 2/1/14
 * Time: 4:48 PM
 * 
 */

namespace OaiPmhTest;

use OaiPmhTools\Client\AbstractAdapter;
use OaiPmhTools\Client\XmlClient;
use OaiPmhTools\OaiServerException;

/**
 * Test
 *
 */
class XmlClientTest extends \PHPUnit_Framework_TestCase
{

    protected $testRepo = 'http://www.avhumboldt.net/oai/oai.php';

    /** @var  XmlCLient */
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

    /** @expectedException RuntimeException */
    public function testNotSettingUriReturnsException()
    {
        $this->client->identify();
    }

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

}
