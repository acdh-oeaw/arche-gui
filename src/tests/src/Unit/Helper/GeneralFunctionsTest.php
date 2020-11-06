<?php

namespace Drupal\Tests\acdh_repo_gui\Unit;

use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Helper;

/**
 * Tests GeneralFunctions
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Helper\GeneralFunctions
 */

class GeneralFunctionsTest extends \PHPUnit\Framework\TestCase
{
    protected static $repo;
    protected static $config;
    private $object;
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5).'/vendor/autoload.php';
        $cfgFile      = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo   = Repo::factory($cfgFile);
    }
    
    public function setUp(): void
    {
        $this->initObject();
    }

    public function initObject() : \Drupal\acdh_repo_gui\Helper\GeneralFunctions
    {
        $this->object = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions(dirname(__DIR__, 1) . '/testconfig.yaml');
        $this->assertInstanceOf(\Drupal\acdh_repo_gui\Helper\GeneralFunctions::class, $this->object);
        return $this->object;
    }
    
    public function testDetailViewUrlDecodeEncode()
    {
        $this->assertEmpty($this->object->detailViewUrlDecodeEncode('', 0));
        $this->assertEquals('https://id.acdh.oeaw.ac.at/test/H115', $this->object->detailViewUrlDecodeEncode('id.acdh.oeaw.ac.at:test:H115', 0));
        $this->assertEquals('https://id.acdh.oeaw.ac.at/uuid/test/H115', $this->object->detailViewUrlDecodeEncode('id.acdh.oeaw.ac.at:uuid:test:H115', 0));
        $this->assertEquals('http://127.0.0.1/api/263325', $this->object->detailViewUrlDecodeEncode('263325', 0));
 
        $stub = $this->createMock(\Drupal\acdh_repo_gui\Helper\GeneralFunctions::class);
        //$stub->method('detailViewUrlDecodeEncode')->willReturn('http://hdl.handle.net/263325');
        //$this->assertEquals('http://hdl.handle.net/263325', $stub->detailViewUrlDecodeEncode('hdl.handle.net:263325', 0));
        
        // 263325 ->https://arche-dev.acdh-dev.oeaw.ac.at/api/263325  0
        $map = [
            ['hdl.handle.net:263325', 0, 'http://hdl.handle.net/263325'],
            ['geonames.org:263325', 0, 'http://geonames.org/263325'],
            ['d-nb.info:263325', 0, 'http://d-nb.info/263325']
        ];
        
        $stub->method('detailViewUrlDecodeEncode')
             ->will($this->returnValueMap($map));

        // $stub->doSomething() returns different values depending on
        // the provided arguments.
        $this->assertSame('http://hdl.handle.net/263325', $stub->detailViewUrlDecodeEncode('hdl.handle.net:263325', 0));
        $this->assertSame('http://geonames.org/263325', $stub->detailViewUrlDecodeEncode('geonames.org:263325', 0));
        $this->assertSame('http://d-nb.info/263325', $stub->detailViewUrlDecodeEncode('d-nb.info:263325', 0));
        
        $service = $this->getMockBuilder(\Drupal\acdh_repo_gui\Helper\GeneralFunctions::class)
        ->disableOriginalConstructor()
        ->setMethods(['detailViewUrlDecodeEncode'])
        ->getMock();
        $service->expects($this->any())
        ->method('detailViewUrlDecodeEncode')
        ->will($this->returnValue('http://d-nb.info/263325'));
        
        $this->assertSame('http://d-nb.info/263325', $service->detailViewUrlDecodeEncode('d-nb.info:263325', 0));
        
        $this->assertSame('hdl.handle.net/263325', $this->object->detailViewUrlDecodeEncode('http://hdl.handle.net/263325', 1));
        $this->assertSame('263325', $this->object->detailViewUrlDecodeEncode('http://127.0.0.1/api/263325', 1));
        $this->assertSame('example.com/263325', $this->object->detailViewUrlDecodeEncode('https://example.com/263325', 1));
        $this->assertSame('example.com/263325', $this->object->detailViewUrlDecodeEncode('http://example.com/263325', 1));
    }
    
    public static function exampleUUIDData()
    {
        return (object) array('id' => '263325');
    }
   
    public function testSpecialIdentifierToUUID()
    {
        $service = $this->getMockBuilder(\Drupal\acdh_repo_gui\Model\GeneralFunctionsModel::class)
        ->disableOriginalConstructor()
        ->setMethods(['getViewData'])
        ->getMock();
        
        $service->expects($this->any())
        ->method('getViewData')
        ->willReturn(array(self::exampleUUIDData()));
     
        $this->assertEquals(array(self::exampleUUIDData()), $service->getViewData());
        
        $service = $this->getMockBuilder(\Drupal\acdh_repo_gui\Helper\GeneralFunctions::class)
        ->disableOriginalConstructor()
        ->setMethods(['detailViewUrlDecodeEncode'])
        ->getMock();
        $service->expects($this->any())
        ->method('detailViewUrlDecodeEncode')
        ->will($this->returnValue('http://hdl.handle.net/263325'));
        
        $this->assertSame('http://hdl.handle.net/263325', $service->detailViewUrlDecodeEncode('hdl.handle.net:263325', 0));
    }
}
