<?php

namespace Drupal\Tests\acdh_repo_gui\Unit;

use acdhOeaw\acdhRepoLib\Repo;


/**
 * Tests GeneralFunctions
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Helper\GeneralFunctions
 */

class GeneralFunctionsTest extends \PHPUnit\Framework\TestCase
{
    protected $repo;
    protected $config;
    protected $object;
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5).'/vendor/autoload.php';
    }
    
    public function setUp(): void
    {
        $cfgFile      = dirname(__DIR__, 1) . '/testconfig.yaml';
        $this->config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        $this->repo   = \acdhOeaw\acdhRepoLib\Repo::factory($cfgFile);
        $this->object = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions(dirname(__DIR__, 1) . '/testconfig.yaml');
    }
    
    public function testDetailViewUrlDecodeEncode() {
        $this->assertEquals('http://127.0.0.1/api/263325', $this->object->detailViewUrlDecodeEncode('263325', 0)); 
        // 263325 ->https://arche-dev.acdh-dev.oeaw.ac.at/api/263325  0
                
    }
}