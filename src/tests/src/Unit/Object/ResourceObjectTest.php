<?php

namespace Drupal\Tests\acdh_repo_gui\Unit\Object;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use DateTime;
use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Object as RO;

/**
 * Tests ResourceObject
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Object\ResourceObject
 */

class ResourceObjectTest extends \PHPUnit\Framework\TestCase
{
    /**
     *
     * @var \acdhOeaw\acdhRepoLib\Repo
     */
    protected static $repo;
    protected static $config;
    private static $object;
    private static $resourceData = array();
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5).'/vendor/autoload.php';
        $cfgFile      = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo   = Repo::factory($cfgFile);
    }
    
    public function setUp(): void
    {
        $this->startTimer();
        $this->createExampleData();
        $this->noteTime('setUp');
    }
    
    public function testInitialization() : \Drupal\acdh_repo_gui\Object\ResourceObject
    {
        self::$object = new \Drupal\acdh_repo_gui\Object\ResourceObject(self::$resourceData, self::$repo);
        $this->assertInstanceOf(\Drupal\acdh_repo_gui\Object\ResourceObject::class, self::$object);
        return self::$object;
    }
    
    private function createExampleData()
    {
        $this->createExampleTitleData();
    }
    
    private function createExampleTitleData()
    {
        self::$resourceData = array();
        $title = new \stdClass();
        $title->id = 345;
        $title->title = 'my example title';
        $title->property = "https://vocabs.acdh.oeaw.ac.at/schema#hasTitle";
        self::$resourceData["acdh:hasTitle"]['en'] = array($title);
    }
    
    private function createExampleIdentifierData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $id->type = 'ID';
        $id->value = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        $id->relvalue = null;
        $id->acdhid = null;
        $id->vocabsid = null;
        $id->accessrestriction = '';
        $id->language = null;
        $id->uri = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        self::$resourceData["acdh:hasIdentifier"]['en'][] = array($id);
    }
    
    private function createExampleAcdhIdentifierData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $id->type = 'ID';
        $id->value = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        $id->relvalue = null;
        $id->acdhid = null;
        $id->vocabsid = null;
        $id->accessrestriction = '';
        $id->language = null;
        $id->uri = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        self::$resourceData["acdh:hasIdentifier"]['en'] = array($id);
    }
    
    private function createExampleAcdhIdentifierIdData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $id->type = 'ID';
        $id->value = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        $id->relvalue = null;
        $id->acdhid = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        $id->vocabsid = null;
        $id->accessrestriction = '';
        $id->language = null;
        $id->uri = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        self::$resourceData["acdh:hasIdentifier"]['en'] = array($id);
    }
    
    private function createExampleAccessRestrictionData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccesssRestriction';
        $id->type = 'REL';
        $id->value = '4685';
        $id->relvalue = null;
        $id->acdhid = null;
        $id->vocabsid = 4685;
        $id->accessrestriction = '';
        $id->language = null;
        $id->uri = 'https://vocabs.acdh.oeaw.ac.at/accesrestriction/public';
        self::$resourceData["acdh:hasAccessRestriction"]['en'] = array($id);
    }
    
    private function createExamplePidData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $id->type = 'ID';
        $id->value = 'https://example.pid.com';
        $id->relvalue = null;
        $id->acdhid = null;
        $id->vocabsid = null;
        $id->accessrestriction = '';
        $id->language = null;
        $id->uri = 'https://example.pid.com';
        self::$resourceData["acdh:hasPid"]['en'] = array($id);
    }
    
    private function createExampleAvailabelDateData()
    {
        $id = new \stdClass();
        $id->id = 345;
        $id->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate';
        $id->type = 'http://www.w3.org/2001/XMLSchema#date';
        $id->value = '2020-07-28 09:39:29';
        $id->relvalue = null;
        $id->acdhid = null;
        $id->vocabsid = null;
        $id->accessrestriction = '';
        $id->language = null;
        $id->title = '2017-10-03';
        $id->shortcut = 'acdh:hasAvailableDate';
        self::$resourceData["acdh:hasAvailableDate"]['en'] = array($id);
    }
    
    public function testGetTitle()
    {
        $this->assertNotEmpty(self::$object->getTitle());
    }
    
    public function testGetData()
    {
        $this->assertNotEmpty(self::$object->getData('acdh:hasTitle'));
        $this->assertEmpty(self::$object->getData('acdh:hasTitle1'));
    }
    
    public function testGetIdentifiers()
    {
        //$this->assertEmpty(self::$object->getIdentifiers());
        //add idenitifier
        $this->createExampleIdentifierData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getIdentifiers());
    }
    
    
    public function testGetNonAcdhIdentifiers()
    {
        //$this->assertEmpty(self::$object->getNonAcdhIdentifiers());
        //add idenitifier
        $this->createExampleAcdhIdentifierData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getNonAcdhIdentifiers());
    }


    public function testGetAcdhID()
    {
        $this->assertEmpty(self::$object->getAcdhID());
        $this->createExampleAcdhIdentifierIdData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getAcdhID());
    }

    public function testGetInsideUrl()
    {
        $this->assertNotEmpty(self::$object->getInsideUrl());
    }
    
    
    public function testGetAvailableDate()
    {
        $this->assertEmpty(self::$object->getAvailableDate());
        $this->createExampleAvailabelDateData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getAvailableDate());
    }
    
    public function testGetPid()
    {
        $this->assertEmpty(self::$object->getPid());
        $this->createExamplePidData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getPid());
    }
    
    public function testAccessRestriction()
    {
        $this->assertEmpty(self::$object->getAccessRestriction());
        $this->createExampleAccessRestrictionData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getAccessRestriction());
    }
    
    public function testCopyResourceLink()
    {
        $this->assertEmpty(self::$object->getCopyResourceLink());
        $this->createExamplePidData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getCopyResourceLink());
        $this->createExampleAcdhIdentifierIdData();
        $this->testInitialization();
        $this->assertNotEmpty(self::$object->getCopyResourceLink());        
    }
    
    protected function startTimer(): void
    {
        $this->time = microtime(true);
    }

    protected function noteTime(string $msg = ''): void
    {
        $t = microtime(true) - $this->time;
        file_put_contents(dirname(__DIR__, 1) . '/time.log', (new DateTime())->format('Y-m-d H:i:s.u') . "\t$t\t$msg\n", \FILE_APPEND);
    }
}
