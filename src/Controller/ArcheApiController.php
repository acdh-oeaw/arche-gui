<?php

namespace Drupal\acdh_repo_gui\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\acdh_repo_gui\Model\ArcheApiModel;
use Drupal\acdh_repo_gui\Helper\ArcheApiHelper;

/**
 * Description of ArcheApiController
 *
 * @author norbertczirjak
 */
class ArcheApiController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $modelData = array();
    private $result = array();

    public function __construct()
    {
        parent::__construct();
        $this->model = new ArcheApiModel();
        $this->helper = new ArcheApiHelper();
    }

    private function createDbHelperObject(array $args): object
    {
        $obj = new \stdClass();
        foreach ($args as $k => $v) {
            $obj->$k = $v;
        }
        return $obj;
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }

        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#Person', 'searchStr' => strtolower($searchStr)));
        //get the data
        $this->modelData = $this->model->getViewData('persons', $obj);

        $this->result = $this->helper->createView($this->modelData, 'persons');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#Place', 'searchStr' => strtolower($searchStr)));

        //get the data
        $this->modelData = $this->model->getViewData('places', $obj);

        $this->result = $this->helper->createView($this->modelData, 'places');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#Publication', 'searchStr' => strtolower($searchStr)));

        //get the data
        $this->modelData = $this->model->getViewData('publications', $obj);

        $this->result = $this->helper->createView($this->modelData, 'publications');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#Concept', 'searchStr' => strtolower($searchStr)));

        //get the data
        $this->modelData = $this->model->getViewData('concepts', $obj);

        $this->result = $this->helper->createView($this->modelData, 'concepts');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#Organisation', 'searchStr' => strtolower($searchStr)));

        //get the data
        $this->modelData = $this->model->getViewData('organisations', $obj);

        $this->result = $this->helper->createView($this->modelData, 'organisations');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#' . ucfirst($type), 'searchStr' => strtolower($searchStr)));

        //get the data
        $this->modelData = $this->model->getViewData('getData', $obj);

        $this->result = $this->helper->createView($this->modelData, 'getData');
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $obj = $this->createDbHelperObject(array('type' => 'https://vocabs.acdh.oeaw.ac.at/schema#' . ucfirst($type), 'baseUrl' => $this->repo->getBaseUrl()));
        //get the data
        $this->modelData = $this->model->getViewData('metadata', $obj);

        $this->result = $this->helper->createView($this->modelData, 'metadata', $lng);
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
        }
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * The pure basic ontology for checking
     * @return Response
     */
    public function repo_baseOntology(string $lng = 'en'): Response
    {
        $response = new Response();

        $obj = new \stdClass();
        $obj->baseUrl = $this->repo->getBaseUrl();
        $obj->language = $lng;
        $obj = $this->createDbHelperObject(array('language' => $lng, 'baseUrl' => $this->repo->getBaseUrl()));

        //get the data
        $this->modelData = $this->model->getViewData('metadataGui', $obj);

        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
        }

        $response->setContent(json_encode($this->modelData));
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

        $obj = $this->createDbHelperObject(array('language' => $lng, 'baseUrl' => $this->repo->getBaseUrl()));
        //get the data
        $this->modelData = $this->model->getViewData('metadataGui', $obj);

        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
        }
        $this->result = $this->helper->createView($this->modelData, 'metadataGui', $lng);
        if (count($this->result) == 0) {
            return new JsonResponse(array("There is no resource"), 404, ['Content-Type' => 'application/json']);
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
    public function repo_getInverseData(string $repoid): Response
    {
        if (empty($repoid)) {
            return new JsonResponse(array("Please provide a search string"), 404, ['Content-Type' => 'application/json']);
        }
        $response = new Response();

        $obj = $this->createDbHelperObject(array('repoid' => $repoid, 'baseUrl' => $this->repo->getBaseUrl()));
        //get the data
        $this->modelData = $this->model->getViewData('inverse', $obj);

        $this->result = $this->helper->createView($this->modelData, 'inverse');

        if (count($this->result) == 0) {
            $this->result = array(array("There is no data", ""));
        }

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
            return new JsonResponse(array("Please provide a repoid"), 404, ['Content-Type' => 'application/json']);
        }

        $obj = $this->createDbHelperObject(array('repoid' => $repoid));
        //get the data
        $this->modelData = $this->model->getViewData('checkIdentifier', $obj);
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("The identifier is free"), 404, ['Content-Type' => 'application/json']);
        }

        $this->result = $this->helper->createView($this->modelData, 'checkIdentifier');
        if (count($this->result) == 0) {
            return new JsonResponse(array("The identifier is free"), 404, ['Content-Type' => 'application/json']);
        }
        $response->setContent(json_encode($this->result));
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
         *  https://domain.com/browser/api/gnd?_format=json
         */

        $response = new Response();

        $obj = new \stdClass();
        //get the data
        $this->modelData = $this->model->getViewData('gndPerson', $obj);
        if (count($this->modelData) == 0) {
            return new JsonResponse(array("There is no data"), 404, ['Content-Type' => 'application/json']);
        }

        $this->result = $this->helper->createView($this->modelData, 'gndPerson');

        if (!isset($this->result["fileLocation"]) || empty($this->result["fileLocation"])) {
            return new JsonResponse(array("There is no data"), 404, ['Content-Type' => 'application/json']);
        }

        $response->setContent(json_encode(array("status" => "File created", "url" => $this->result["fileLocation"])));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Generate the counted Collections and binaries text for the gui
     * @param string $lng
     * @return Response
     */
    public function repo_getOntologyJSPluginData(string $lng = 'en'): Response
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
            return new JsonResponse(array("There is no data"), 404, ['Content-Type' => 'application/json']);
        }

        $this->result = $this->helper->createView($this->modelData, 'countCollsBins', $lng);
        $response->setContent(json_encode($this->result));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get the Members list for the gui Organisations Members function
     * @param string $repoid
     * @return Response
     */
    public function repo_getMembers(string $repoid): Response
    {
        /*
         * Usage:
         *  https://domain.com/browser//api/getMembers/{repoid}?_format=json
         */

        $response = new Response();
        $obj = $this->createDbHelperObject(array('repoid' => $repoid, 'lang' => $this->siteLang));

        //get the data
        $this->modelData = $this->model->getViewData('getMembers', $obj);

        if (count($this->modelData) == 0) {
            $this->result = array(array("There is no data"));
            goto end;
        }

        $this->result = $this->helper->createView($this->modelData, 'getMembers', $this->siteLang);
        end:
        $response->setContent(json_encode(array('data' => $this->result)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Get the RelatedPublicationsResources list for the gui basic view
     * @param string $repoid
     * @return Response
     */
    public function repo_getRelatedPublicationsResources(string $repoid, string $lng = 'en'): Response
    {
        /*
         * Usage:
         *  https://domain.com/browser/api/getRPR/{repoid}?_format=json
         */

        $response = new Response();
        $obj = $this->createDbHelperObject(array('repoid' => $repoid, 'lang' => $lng));

        //get the data
        try {
            $this->modelData = $this->model->getViewData('getRPR', $obj);
            if (count($this->modelData) == 0) {
                $this->result = array(array("There is no data", "", ""));
                goto end;
            }

            $this->result = $this->helper->createView($this->modelData, 'getRPR', $this->siteLang);
            $response->setStatusCode(200);
        } catch (\Exception $ex) {
            $response->setStatusCode(400);
            $this->result = $ex->getMessage();
        }
        
        end:
        $response->setContent(json_encode(array('data' => $this->result)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }
    
    public function repo_getRelatedPublicationsResourcesAjax(string $repoid, string $limit, string $page, string $order, string $lng = 'en'): Response
    {
        /*
         * Usage:
         *  https://domain.com/browser/api/getRPR/{repoid}?_format=json
         */
        
        $response = new Response();
        $obj = $this->createDbHelperObject(
            array('fieldOrder' => true, 'repoid' => $repoid,
            'lang' => $lng, 'limit' => $limit, 'page' => $page, 'order' => $order,
            'fields' => array('titleasc' => 'title', 'titledesc' => 'title',
                'typeasc' => 'acdhtype', 'typedesc' => 'acdhtype')
                )
        );

        //get the data
        $this->modelData = $this->model->getViewData('getRPRAjax', $obj);

        if (count($this->modelData) == 0) {
            $this->result = array(array("There is no data", "", ""));
            goto end;
        }

        $this->result = $this->helper->createView($this->modelData, 'getRPR', $this->siteLang);
        end:
        $response->setContent(json_encode(array('data' => $this->result)));
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * Create root table clone based on the actual ontology
     * @param string $lng
     * @return Response
     */
    public function repo_getRootTable(string $lng = 'en')
    {
        /*
         * Usage:
         *  https://domain.com/browser/api/getRootTable/en?_format=json
         */

        $response = new Response();
        $obj = $this->createDbHelperObject(array('baseUrl' => $this->repo->getBaseUrl(), 'language' => $lng));

        //get the data
        $this->modelData = $this->model->getViewData('rootTable', $obj);

        if (count($this->modelData) == 0) {
            $response->setContent('No data!');
            $response->setStatusCode(200);
        }

        $this->result = $this->helper->createView($this->modelData, 'rootTable', $lng);

        if (isset($this->result[0]) && !empty($this->result[0])) {
            $response->setContent($this->result[0]);
            $response->setStatusCode(200);
        } else {
            $response->setContent('No data!');
            $response->setStatusCode(200);
        }

        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }
    
    
    
   
}
