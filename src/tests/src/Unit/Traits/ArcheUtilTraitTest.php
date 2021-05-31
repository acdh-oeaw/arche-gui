<?php

namespace Drupal\Tests\acdh_repo_gui\Unit\Traits;

require_once dirname(__DIR__, 1) . '/PHPUnitUtil.php';

/**
 * Tests ArcheUtilTrait
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait
 */
class ArcheUtilTraitTest extends \PHPUnit\Framework\TestCase
{
    private static $config;
    private static $repo;
    private $absObj;
    
    protected function setUp(): void
    {
        parent::setUp();
        //$this->initAbstract();
    }
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5) . '/vendor/autoload.php';
        $cfgFile = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file(dirname(__DIR__, 1) . '/testconfig.yaml')));
        self::$repo = \acdhOeaw\arche\lib\Repo::factory(dirname(__DIR__, 1) . '/testconfig.yaml');
    }
    
    /*
    public function initAbstract(): void
    {
        $this->absObj = $this->getMockForAbstractClass(\Drupal\acdh_repo_gui\Helper\ArcheHelper::class, [dirname(__DIR__, 1) . '/testconfig.yaml']);
    }

    public function testCreateShortCut()
    {
        $returnVal = \PHPUnitUtil::callMethod(
            $this->absObj,
            'createShortcut',
            array('http://www.loc.gov/premis/rdf/v1#premisTest')
        );
        $this->assertSame('premis:premisTest', $returnVal);

        $retVal = \PHPUnitUtil::callMethod(
                $this->absObj,
                'createShortcut',
                array('https://id.acdh.oeaw.ac.at/5671'));
        $this->assertSame('acdhID:5671', $retVal);
    }

    public function testEmptyLanguage() {

        $obj = new \stdClass();
        $retVal = \PHPUnitUtil::callMethod(
                $this->absObj,
                'setLanguage',
                array($obj));
        $this->assertSame('en', $retVal);
    }

    public function testObjLanguage() {

        $obj = new \stdClass();
        $obj->language = "en";
        $retVal = \PHPUnitUtil::callMethod(
                $this->absObj,
                'setLanguage',
                array($obj));
        $this->assertSame('en', $retVal);
    }
*/
}
