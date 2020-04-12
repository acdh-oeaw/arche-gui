<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use Drupal\acdh_repo_gui\Model\ArcheApiModel;
use Drupal\acdh_repo_gui\Helper\ArcheApiHelper;
/**
 * Description of ArcheApiController
 *
 * @author norbertczirjak
 */
class ArcheApiController extends ControllerBase 
{    
    private $config;
    private $repo;
    private $siteLang;
    private $helper;
    private $model;
    private $modelData = array();
    
    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->model = new ArcheApiModel();
        $this->helper = new ArcheApiHelper();
    }
    
   
    public function repo_persons(string $searchStr): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/persons/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Person';
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('persons', $obj);
        
        $result = $this->helper->createView($this->modelData, 'persons');
        
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
}