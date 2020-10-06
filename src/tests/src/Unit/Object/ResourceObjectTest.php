<?php

namespace Drupal\Tests\acdh_repo_gui\Unit\Object;

require_once dirname(__DIR__, 1) . '/ExampleData.php';
   
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
    private static $emptyObject;
    private static $resourceData = array();
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5).'/vendor/autoload.php';
        $cfgFile      = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo   = Repo::factory($cfgFile);
        self::$resourceData = \ExampleData::exampleResourceData();
    }
    
    public function setUp(): void
    {
        $this->startTimer();
        $this->initEmptyObject();
        $this->initObject();
        $this->noteTime('setUp');
    }
    
    public function initObject() : \Drupal\acdh_repo_gui\Object\ResourceObject
    {
        self::$object = new \Drupal\acdh_repo_gui\Object\ResourceObject(self::$resourceData, self::$repo);
        $this->assertInstanceOf(\Drupal\acdh_repo_gui\Object\ResourceObject::class, self::$object);
        return self::$object;
    }
    
    public function initEmptyObject() : \Drupal\acdh_repo_gui\Object\ResourceObject
    {
        $noData = array();
        $resourceData = array();
        $data = new \stdClass();
        $data->id = 345;
        $data->value = 'my example title';
        $data->title = 'my example title';
        $data->property = "https://vocabs.acdh.oeaw.ac.at/schema#hasTitle1";
        $noData["acdh:hasTitle1"]['en'] = array($data);
        
        self::$emptyObject = new \Drupal\acdh_repo_gui\Object\ResourceObject($noData, self::$repo);
        $this->assertInstanceOf(\Drupal\acdh_repo_gui\Object\ResourceObject::class, self::$emptyObject);
        return self::$emptyObject;
    }
  
    /**
    * @outputBuffering disabled
    */
    /*
    public function testBeforeTitleOutput() {
       
        print_r("THE TITLE BEFORE");
        var_dump(self::$object->getData('acdh:hasIdentifier'));
    }   
*/
    
    public function testGetTitle()
    {
        $this->assertEmpty(self::$emptyObject->getTitle());
        $this->assertNotEmpty(self::$object->getTitle());
    }
    
    public function testGetData()
    {
        $this->assertNotEmpty(self::$object->getData('acdh:hasTitle'));
        $this->assertEmpty(self::$object->getData('acdh:hasTitle1'));
    }
    
    public function testGetIdentifiers()
    {
        $this->assertEmpty(self::$emptyObject->getIdentifiers());
        $this->assertNotEmpty(self::$object->getIdentifiers());
    }    
    
    public function testGetNonAcdhIdentifiers()
    {
        $this->assertEmpty(self::$emptyObject->getNonAcdhIdentifiers());
        $this->assertNotEmpty(self::$object->getNonAcdhIdentifiers());
        $this->assertIsArray(self::$object->getNonAcdhIdentifiers());   
    }


    public function testGetAcdhID()
    {
        $this->assertEmpty(self::$emptyObject->getAcdhID());
        $this->assertNotEmpty(self::$object->getAcdhID());
    }

    public function testGetInsideUrl()
    {
        $this->assertNotEmpty(self::$object->getInsideUrl());
    }
    
    
    public function testGetAvailableDate()
    {
        $this->assertEmpty(self::$emptyObject->getAvailableDate());
        $this->assertNotEmpty(self::$object->getAvailableDate());
    }
   
    public function testGetPid()
    {
        $this->assertEmpty(self::$emptyObject->getPid());
        $this->assertNotEmpty(self::$object->getPid());
    }
     
    public function testAccessRestriction()
    {
        $this->assertEmpty(self::$emptyObject->getAccessRestriction());
        $this->assertNotEmpty(self::$object->getAccessRestriction());
    }
    
    public function testCopyResourceLink()
    {
        $this->assertEmpty(self::$emptyObject->getCopyResourceLink());
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
