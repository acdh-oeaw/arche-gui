<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoLib\SearchConfig;
use acdhOeaw\acdhRepoLib\RepoResourceInterface;
use acdhOeaw\acdhRepoLib\SearchTerm;
use Drupal\acdh_repo_gui\Controller\RootViewController as RVC;
use Drupal\acdh_repo_gui\Controller\DetailViewController as DVC;
use Drupal\acdh_repo_gui\Controller\SearchViewController as SVC;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;


/**
 * Description of AcdhRepoController
 *
 * @author nczirjak
 */
class AcdhRepoGuiController extends ControllerBase 
{    
    private $config;
    private $repo;
    private $rootViewController;
    private $searchViewController;
    private $detailViewController;
    private $dissServController;
    private $siteLang;
    private $langConf;
    
    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
         
        $this->rootViewController = new RVC($this->repo);
        $this->searchViewController = new SVC($this->repo);
        $this->detailViewController = new DVC($this->repo);
        $this->dissServController = new DisseminationServicesController($this->repo);
        $this->generalFunctions = new GeneralFunctions();
        $this->langConf = $this->config('acdh_repo_gui.settings');
    }
    
    /**
     * 
     * Root view
     * 
     * @return array
     */
    public function repo_root(string $limit = "10", string $page = "1", string $order = "datedesc"): array
    {
        $limit = (int)$limit;
        $page = (int)$page;
        // on the gui we are displaying 1 as the first page.
        //$page = $page-1;
        $count = 0;
        $count = $this->rootViewController->countRoots();
        
        $roots = array();
        $paging = array();
        if((int)$count > 0){
            $roots = $this->rootViewController->generateRootView((int)$limit, (int)$page, $order);
        }
        
        if(!isset($roots['data']) || count($roots['data']) <= 0) {
            drupal_set_message(
                $this->langConf->get('errmsg_no_root_resources') ? $this->langConf->get('errmsg_no_root_resources') : 'You do not have Root resources',
                'error',
                false
            );
            return array();
        }
        
        if( count($roots['pagination']) > 0 ) {
            $paging = $roots['pagination'][0];
        }
       
        return [
            '#theme' => 'acdh-repo-gui-main',
            '#data' => $roots['data'],
            '#paging' => $paging,
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-root-view',
                ]
            ]
        ]; 
        
    }
    
    /**
     * Repo search/root main view
     * 
     * @param string $metavalue
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return array
     */
    public function repo_complexsearch(string $metavalue = "root", string $limit = "10", string $page = "1", string $order = "titleasc"): array
    {   
        //this is the root collection view
        if (empty($metavalue) ||  $metavalue == "root") {
            //If a cookie setting exists and the query is coming without a specific parameter
            if ((isset($_COOKIE["resultsPerPage"]) && !empty($_COOKIE["resultsPerPage"])) && empty($limit)) {
                $limit = $_COOKIE["resultsPerPage"];
            }
            if ((isset($_COOKIE["resultsOrder"]) && !empty($_COOKIE["resultsOrder"])) && empty($order)) {
                $order = $_COOKIE["resultsOrder"];
            }
            if (empty($page)) {
                $page = "1";
            }
            return $this->repo_root($limit, $page, $order);
        } 
        
        //we have the search view
        
        //the search view
        $paging = array();
        $searchResult = array();
        $searchResult = $this->searchViewController->generateView($limit, $page, $order, $metavalue);
        
        if(count($searchResult['data']) <= 0) {
            drupal_set_message(
                $this->langConf->get('errmsg_no_search_res') ? $this->langConf->get('errmsg_no_search_res') : 'Your search yielded no results.',
                'error',
                false
            );
            return array();
        }
        
        if( count($searchResult['pagination']) > 0 ) {
            $paging = $searchResult['pagination'][0];
        }
        
        return [
            '#theme' => 'acdh-repo-gui-main',
            '#data' => $searchResult['data'],
            '#paging' => $paging,
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-root-view',
                ]
            ]
        ]; 
    }
    
    /**
     * the detail view
     * 
     * @param string $identifier
     * @return type
     */
    public function repo_detail(string $identifier)
    {   
        $ajax = false;
        
        if (strpos($identifier, '&ajax') !== false) {
            $identifier = explode('&', $identifier);
            $identifier = $identifier[0];
            $ajax = true;
        }
        
        $dv = array();
        $identifier = $this->generalFunctions->detailViewUrlDecodeEncode($identifier, 0);
        $dv = $this->detailViewController->generateDetailView($identifier);
        if(count((array)$dv) < 1) {
             drupal_set_message(
                $this->langConf->get('errmsg_no_data') ? $this->langConf->get('errmsg_no_data') : 'You do not have data',
                'error',
                false
            );
            return array();
        }
        
        $return = [
            '#theme' => 'acdh-repo-gui-detail',
            '#basic' => $dv->basic,
            '#extra' => $dv->extra,
            '#dissemination' => (isset($dv->dissemination)) ? $dv->dissemination : array(),
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ]
        ]; 
        if($ajax){
            return new Response(render($return));
        }
        return $return;
        
        
    }
    
    public function search_view(string $data) {
        
        
        //$log = new \zozlak\logging\Log(drupal_get_path('module', 'acdh_repo_gui').'/zozlaklog', \Psr\Log\LogLevel::DEBUG);
        
        $config = new \acdhOeaw\acdhRepoLib\SearchConfig();
        $config->metadataMode = RepoResourceInterface::META_RESOURCE; 
        $config->ftsQuery             = $data;
        $repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml', 'guest');
        //$repodb->setQueryLog($log);
        
        $resTitle = $repodb->getPdoStatementBySearchTerms(
                [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', $data, '@@')], 
                $config)->fetchAll();
        $resDesc = $repodb->getPdoStatementBySearchTerms(
                [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', $data, '@@')], 
                $config)->fetchAll();
        
        $results = array_merge($resTitle, $resDesc);
        $finalRes = array();
        foreach ($results as $res) {
            
            if($res['property']  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle') {
                $finalRes[] = $res;
            }
             if($res['property']  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription') {
                $finalRes[] = $res;
            }
        }
        
        echo "<pre>";
        var_dump($finalRes);
        echo "</pre>";
        die();
       
        
        return [
            '#theme' => 'acdh-repo-gui-search',
            '#result' => "sss",
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ]
        ]; 
    }
    
    
    ////////// DISSEMINATION SERVICES /////////
    
    /**
     * Download Whole Collection python script
     *
     * @param string $url
     * @return Response
     */
    public function oeaw_get_collection_dl_script(string $repoid): Response
    {
        if(empty($repoid)) {
            $result = '';
        }else {
            $repoid = $this->repo->getBaseUrl().$repoid;
            $result = $this->generalFunctions->changeCollDLScript($repoid);
        }
        
        $response = new Response();
        $response->setContent($result);
        $response->headers->set('Content-Type', 'application/x-python-code');
        $response->headers->set('Content-Disposition', 'attachment; filename=collection_download_script.py');
        return $response;
    }
    
    /**
     * This API will generate the turtle file from the resource.
     *
     * @param string $identifier - the UUID
     * @param string $page
     * @param string $limit
     */
    public function oeaw_turtle_api(string $repoid): Response
    {
        if (!empty($repoid)) {
            $result = array();
            $result = $this->dissServController->generateView($repoid, 'turtle_api');
            if(count($result) > 0) {
                return new Response($result[0], 200, ['Content-Type'=> 'text/turtle']);
            }
        }
        return new Response("No data!", 400);
    }
    
    /**
     * The collection view GUI view with the metadata and the js treeview
     * 
     * @param string $repoid
     * @return type
     */
    public function oeaw_dl_collection_view(string $repoid) {
        $view = array();
        $repoid = $this->generalFunctions->detailViewUrlDecodeEncode($repoid, 0);
        
        $extra['metadata'] = $this->detailViewController->generateObjDataForDissService($repoid);
        $extra['repoid'] = $repoid;
        
        return [
            '#theme' => 'acdh-repo-ds-dl-collection',
            '#basic' => $view,
            '#extra' => $extra,
            '#cache' => ['max-age' => 0,], 
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-collection-dl',
                ]
            ]
        ]; 
    }
    
    /**
     *
     * This generates the jstree data for the collection download view
     *
     * @param string $uri
     * @return Response
    */
    public function oeaw_get_collection_data(string $repoid) : Response
    {
        $result = array();
        if (empty($repoid)) {
            $errorMSG = t('Missing').': Identifier';
        } else {
            $DSC = new DisseminationServicesController($this->repo);
            $result = $DSC->generateView($repoid, 'collection');
        }
        
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     * Display the 3d object (nxs, ply) inside a js viewer
     * 
     * @param string $repoid -> repoid only
     * @return array
     */
    public function oeaw_3d_viewer(string $repoid) : array
    {   
        $basic = array();
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl().$repoid;
            $result = array();
            $result = $this->dissServController->generateView($repoid, '3d');
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        
            if(count($result) > 0 && isset($result['result'])) {
                return
                    array(
                        '#theme' => 'acdh-repo-ds-3d-viewer',
                        '#ObjectUrl' => $result['result'],
                        '#basic' => $basic
                    );
            }
        }
        return
            array(
                '#theme' => 'acdh-repo-ds-3d-viewer',
                '#ObjectUrl' => $result['result'],
                '#basic' => $basic
            );
    }
    
    public function oeaw_dl_collection_binaries(string $repoid) : Response
    {
        
    }
    
    
    /**
     * Generate loris url based on the repoid and passing it back to the iiif template
     * 
     * @param string $repoid -> repoid
     * @return array
     */
    public function oeaw_iiif_viewer(string $repoid) : array
    {
        //RepoResource->getDissServ()['rawIIIf']->getUrl() -> when it is ready
        $basic = array();
        $lorisUrl = '';
        
        $repoUrl = $this->repo->getBaseUrl().$repoid;
        $result = array();
        $result = $this->dissServController->generateView($repoid, 'iiif');
        if(isset($result['lorisUrl']) && !empty($result['lorisUrl']))
        {
           $lorisUrl = $result['lorisUrl'];
        }
        $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        
        return
            array(
                '#theme' => 'acdh-repo-ds-iiif-viewer',
                '#basic' => $basic,
                '#lorisUrl' => $lorisUrl
            );
    }
    
     /**
     * Change language session variable API
     * Because of the special path handling, the basic language selector is not working
     *
     * @param string $lng
     * @return Response
    */
    public function oeaw_change_lng(string $lng = 'en'): Response
    {
        $_SESSION['language'] = strtolower($lng);
        $response = new Response();
        $response->setContent(json_encode("language changed to: ".$lng));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    
    
}
