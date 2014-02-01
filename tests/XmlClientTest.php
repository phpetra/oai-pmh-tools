<?php
/**
 * @author PHPetra
 * Date: 2/1/14
 * Time: 4:48 PM
 * 
 */

namespace OaiPmhTest;

use OaiPmhTools\Client\XmlClient;
use OaiPmhTools\RuntimeException;

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



}
