<?php

namespace Drupal\Tests\acdh_repo_gui\Unit\Controller;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use acdhOeaw\arche\lib\Repo;
use Drupal\acdh_repo_gui\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Tests ArcheApiController
 *
 * @group acdh_repo_gui
 * @coversDefaultClass \Drupal\acdh_repo_gui\Controller\ArcheApiController
 */

class ArcheApiControllerTest extends \PHPUnit\Framework\TestCase
{

     /**
     * @var \acdhOeaw\arche\lib\Repo
     */
    protected static $repo;
    protected static $config;
    private $model;
    private $helper;
    private $repodb;
    
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__, 5).'/vendor/autoload.php';
        $cfgFile      = dirname(__DIR__, 1) . '/testconfig.yaml';
        self::$config = json_decode(json_encode(yaml_parse_file($cfgFile)));
        self::$repo   = Repo::factory($cfgFile);
        $this->model = new ArcheApiModel();
        $this->helper = new ArcheApiHelper();
        $this->repodb = \acdhOeaw\arche\lib\RepoDb::factory($this->config);
    }
    
    
    
    /*
    * @dataProvider provider
    */
    public function repo_personsTest(string $searchStr) {
         $this->assertInstanceOf(Response);
    }
    
    
}