<?php

namespace Drupal\Tests\acdh_repo_gui\Unit\Object;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use DateTime;
use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Object as RO;

/**
 * @coversDefaultClass \Drupal\acdh_repo_gui\Object\ResourceObject
 * @group acdh_repo_gui
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
        self::$object = new \Drupal\acdh_repo_gui\Object\ResourceObject(self::$resourceData, self::$config);
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
