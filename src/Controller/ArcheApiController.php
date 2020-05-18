<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
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
    private $repodb;
    private $siteLang;
    private $helper;
    private $model;
    private $modelData = array();
    private $result = array();
    
    public function __construct()
    {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->model = new ArcheApiModel();
        $this->helper = new ArcheApiHelper();
        $this->repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory($this->config);
    }
    
    /**
     * Get the Persons data for the Metadata Editor
     * @param string $searchStr
     * @return Response
     */
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
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the Places data for the Metadata Editor
     * @param string $searchStr
     * @return Response
     */
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
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the publications data for the Metadata Editor
     * @param string $searchStr
     * @return Response
     */
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
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the concepts data for the Metadata Editor
     * @param string $searchStr
     * @return Response
     */
    public function repo_concepts(string $searchStr): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/concepts/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($searchStr)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Concept';
        $obj->searchStr = strtolower($searchStr);
        //get the data
        $this->modelData = $this->model->getViewData('concepts', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'concepts');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the organisations data for the Metadata Editor
     * @param string $searchStr
     * @return Response
     */
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
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the user defined acdh type data for the Metadata Editor
     * @param string $type
     * @param string $searchStr
     * @return Response
     */
    public function repo_getData(string $type, string $searchStr): Response
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
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the ontology metadata
     * @param string $type
     * @param string $lng
     * @return Response
     */
    public function repo_getMetadata(string $type, string $lng): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/getMetadata/TYPE/en?_format=json
        */
        
        $response = new Response();
        
        if (empty($lng) && empty($type)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#'.ucfirst($type);
        $obj->baseUrl = $this->repo->getBaseUrl();
        //get the data
        $this->modelData = $this->model->getViewData('metadata', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'metadata', $lng);
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the ontology metadata for the JS plugin table
     * @param string $lng
     * @return Response
     */
    public function repo_getMetadataGui(string $lng = 'en'): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/getMetadataGui/en?_format=json
        * "optional" means "$min empty or equal to 0"
        * "mandatory" is "$min greater than 0 and $recommended not equal true"
        * "recommended" is "$min greater than 0 and $recommended equal to true"
        */
        
        $response = new Response();
        
        $obj = new \stdClass();
        $obj->baseUrl = $this->repo->getBaseUrl();
        $obj->language = $lng;
        //get the data
        $this->modelData = $this->model->getViewData('metadataGui', $obj);
        
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $this->result = $this->helper->createView($this->modelData, 'metadataGui', $lng);
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Get the actual resource inverse data
     * Where the id is available, but not identifier, pid or ispartof
     * @return Response
     */
    public function repo_inverse_result(string $repoid): Response
    {
        if (empty($repoid)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->repoid = $repoid;
        $obj->baseUrl = $this->repo->getBaseUrl();
        //get the data
        $this->modelData = $this->model->getViewData('inverse', $obj);
        
        $this->result = $this->helper->createView($this->modelData, 'inverse');
        
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $response = new Response();
        $response->setContent(json_encode(array('data' => $this->result)));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     * Check the repoid is already available in the system or not
     * $repoid = number
     * @param string $repoid
     * @return Response
     */
    public function repo_checkIdentifier(string $repoid): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/getData/checkIdentifier/MYVALUE?_format=json
        */
        
        $response = new Response();
        
        if (empty($repoid)) {
            return new JsonResponse(array("Please provide a repoid"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->repoid = (int)$repoid;
        //get the data
        $this->modelData = $this->model->getViewData('checkIdentifier', $obj);
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("The identifier is free"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $this->result = $this->helper->createView($this->modelData, 'checkIdentifier');
        if (count($this->result) == 0) {
            return new JsonResponse(array("The identifier is free"), 404, ['Content-Type'=> 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * Recache the controlled vocabs
     *
     * @param string $lng
     * @return Response
     */
    public function repo_recacheControlledVocabs(string $lng): Response
    {
        $response = new Response();
        
        ini_set('max_execution_time', 3600);
        ini_set('max_input_time', 360);
                 
        $helper = new \Drupal\acdh_repo_gui\Helper\CacheVocabsHelper($lng);
        $this->result = array();
        $this->result = $helper->getControlledVocabStrings();
       
        if (isset($this->result['error'])) {
            return new JsonResponse(array($this->result['error']." not cached/generated"), 404, ['Content-Type'=> 'application/json']);
        }
     
        $response->setContent(json_encode("Update is ready!"));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
    /**
     * The gnd file generation API endpoint
     * @return Response
     */
    public function repo_gndPerson(): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/getData/gnd?_format=json
        */
        
        $response = new Response();
        
        $obj = new \stdClass();
        //get the data
        $this->modelData = $this->model->getViewData('gndPerson', $obj);
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no data"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $this->result = $this->helper->createView($this->modelData, 'gndPerson');
        
        $response->setContent(json_encode(array("status" => "File created", "url" => $fileLocation)));
        if (!isset($this->result["fileLocation"]) || empty($this->result["fileLocation"])) {
            return new JsonResponse(array("There is no data"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $response->setContent(json_encode(array("status" => "File created", "url" => $this->result["fileLocation"])));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
    
   
    public function repo_getOntologyJSPluginData(): Response
    {
        /*
        * Usage:
        *  https://domain.com/browser/api/getOntologyJSPluginData/Language?_format=json
        */
        
        $response = new Response();
        
        $obj = new \stdClass();
        //get the data
        $this->modelData = $this->model->getViewData('countCollsBins', $obj);
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no data"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $this->result = $this->helper->createView($this->modelData, 'countCollsBins');
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');
                
        return $response;
    }
}
