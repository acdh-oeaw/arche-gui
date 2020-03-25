<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use Drupal\acdh_repo_gui\Model\RepoApiModel;


use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

use GuzzleHttp\Client;
/**
 * Description of RepoAPIController
 *
 * @author nczirjak
 */
class RepoApiController extends ControllerBase {
    
    private $config;
    private $repo;
    
    public function __construct() {
        
        $_SERVER["DOCUMENT_ROOT"].'/modules/custom/acdh_repo_gui/';
        $this->config = $_SERVER["DOCUMENT_ROOT"].'/modules/custom/acdh_repo_gui/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        
        //$this->rootViewController = new RVC($this->config);
        //$this->detailViewController = new DVC($this->config);
        //$this->generalFunctions = new GeneralFunctions();
        //$this->langConf = $this->config('acdh_repo_gui.settings');
    }
    
    /**
     * This API will generate the child html view.
     *
     * @param string $identifier - the UUID
     * @param string $page
     * @param string $limit
     */
    public function repo_child_api(string $identifier, string $limit, string $page, string $order): Response
    {
        if (strpos($identifier, $this->repo->getSchema()->__get('drupal')->uuidNamespace) === false) {
            $identifier = $this->repo->getSchema()->__get('drupal')->uuidNamespace.$identifier;
        }
        
        $childArray = $this->oeawFunctions->generateChildAPIData($identifier, (int)$limit, (int)$page, $order);
         
        if (count($childArray['childResult']) == 0) {
            $childArray['errorMSG'] =
                $this->langConf->get('errmsg_no_child_resources') ? $this->langConf->get('errmsg_no_child_resources') : 'There are no Child resources';
        }
        
        $childArray['language'] = \Drupal::languageManager()->getCurrentLanguage()->getId();
        
        $build = [
            '#theme' => 'oeaw_child_view',
            '#result' => $childArray,
            '#attached' => [
                'library' => [
                    'oeaw/oeaw-styles', //include our custom library for this response
                ]
            ]
        ];
        
        return new Response(render($build));
    }
    
}
