<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\arche\Ontology;
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
    private $repodb;
    private $siteLang;
    private $helper;
    private $model;
    private $modelData = array();
    private $result = array();
    
    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        $this->repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory($this->config);
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
        
        $this->result = $this->helper->createView($this->modelData, 'persons');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    public function repo_places(string $searchStr): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/places/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Place';
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('places', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'places');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    public function repo_publications(string $searchStr): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/publications/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Publication';
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('publications', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'publications');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    public function repo_organisations(string $searchStr): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/organisations/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Organisation';
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('organisations', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'organisations');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    public function repo_getData(string $type ,string $searchStr): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/getData/Person/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr) && empty($type)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#'.ucfirst($type);
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('getData', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'getData');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    public function repo_getMetadata(string $type ,string $lng): Response
    {   
        /*
        * Usage:
        *  https://domain.com/browser/api/getMetadata/TYPE/en?_format=json
        */
        
        $response = new Response();
        
        if (empty($lng) && empty($type)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        
        $ontology = new \acdhOeaw\arche\Ontology($this->repodb, 'https://repo.hephaistos.arz.oeaw.ac.at');
        echo "<pre>";
        var_dump($this->repo->getBaseUrl());
        var_dump($ontology);
        echo "</pre>";
        
        die();
        $class = $ontology->getClass($classUri);



        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#'.ucfirst($type);
        
        //get the data
        $this->modelData = $this->model->getViewData('getMetadata', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'getMetadata');
        if(count($this->result) == 0 ){
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
}