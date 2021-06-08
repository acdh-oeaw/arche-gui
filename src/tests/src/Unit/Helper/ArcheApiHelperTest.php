<?php

namespace Drupal\Tests\acdh_repo_gui\Unit;

/**
 * Tests ArcheApiHelper
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Helper\ArcheApiHelper
 */
class ArcheApiHelperTest extends \PHPUnit\Framework\TestCase
{
    protected static $repo;
    protected static $config;
    private $object;
    private $mdStub;

    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5) . '/vendor/autoload.php';
        $cfgFile = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo = \acdhOeaw\arche\lib\Repo::factory($cfgFile);
    }
    
    public function setUp(): void
    {
        $this->initObject();
        $this->mdStub = $this->createMock(\Drupal\acdh_repo_gui\Helper\MetadataGuiHelper::class);
    }
    
    public function initObject(): \Drupal\acdh_repo_gui\Helper\ArcheApiHelper
    {
        $this->object = new \Drupal\acdh_repo_gui\Helper\ArcheApiHelper();
        $this->assertInstanceOf(\Drupal\acdh_repo_gui\Helper\ArcheApiHelper::class, $this->object);
        return $this->object;
    }
}
