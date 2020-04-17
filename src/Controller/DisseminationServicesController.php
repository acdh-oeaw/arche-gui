<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use Drupal\acdh_repo_gui\Model\DisseminationServicesModel;
use Drupal\acdh_repo_gui\Helper\DisseminationServicesHelper;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Controller\DetailViewController as DVC;

/**
 * Description of DisseminationServicesController
 *
 * @author norbertczirjak
 */
class DisseminationServicesController extends ControllerBase {
    
    private $config;
    private $repo;
    private $model;
    private $helper;
    private $basicViewData;
    private $extraViewData;
    private $generalFunctions;
    private $detailViewController;
    
    private $disseminations = array(
        'collection', '3d', 'iiif', 'turtle_api'
    );
    
    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        $this->model = new DisseminationServicesModel();
        $this->helper = new DisseminationServicesHelper($this->repo);
        $this->generalFunctions = new GeneralFunctions();
        $this->detailViewController = new DVC($this->repo);
    }
    
    public function generateView(string $identifier, string $dissemination): array {
        if(empty($identifier) || !in_array($dissemination, $this->disseminations)){
            return array();
        }
        $vd = array();
        if($dissemination == 'collection') {
            $vd = $this->model->getViewData($identifier, $dissemination);
            if(count((array)$vd) == 0) {
                return array();
            }
        }
        
        $this->basicViewData = $this->helper->createView($vd, $dissemination, $identifier);
        return $this->basicViewData;
    }
    
    public function repo_dl_collection_binaries(string $repoid) : Response
    {
        $GLOBALS['resTmpDir'] = "";
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');
        $fileLocation = '';
        //the binary files
        (json_decode($_POST['jsonData'], true)) ? $binaries = json_decode($_POST['jsonData'], true) : $binaries = array();
        if (count($binaries) == 0) {
            $response->setContent(json_encode(""));
            return $response;
        }
        ($_POST['username']) ? $username = $_POST['username'] : $username = '';
        ($_POST['password']) ? $password = $_POST['password'] : $password = '';
       
        $fileLocation = $this->helper->collectionDownload($binaries, $repoid, $username, $password);
        $response->setContent(json_encode($fileLocation));
        return $response;
    }
    
    /**
     *
     * This generates the jstree data for the collection download view
     *
     * @param string $uri
     * @return Response
    */
    public function repo_get_collection_data(string $repoid) : Response
    {
        $result = array();
        if (empty($repoid)) {
            $errorMSG = t('Missing').': Identifier';
        } else {
            $result = $this->generateView($repoid, 'collection');
        }
        
        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
    
    /**
     * The collection view GUI view with the metadata and the js treeview
     * 
     * @param string $repoid
     * @return type
     */
    public function repo_dl_collection_view(string $repoid) {
        $view = array();
        $repoid = $this->generalFunctions->detailViewUrlDecodeEncode($repoid, 0);
        
        $extra['metadata'] = $this->detailViewController->generateObjDataForDissService($repoid);
        $extra['repoid'] = $repoid;
        
        return [
            '#theme' => 'acdh-repo-ds-dl-collection',
            '#basic' => $view,
            '#extra' => $extra,
            '#cache' => ['max-age' => 0], 
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-collection-dl',
                ]
            ]
        ]; 
    }
    
    /**
     * Download Whole Collection python script
     *
     * @param string $url
     * @return Response
     */
    public function repo_get_collection_dl_script(string $repoid): Response
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
    public function repo_turtle_api(string $repoid): Response
    {
        
        if (!empty($repoid)) {
            $result = array();
            $result = $this->generateView($repoid, 'turtle_api');
            if(count($result) > 0) {
                return new Response($result[0], 200, ['Content-Type'=> 'text/turtle']);
            }
        }
        return new Response("No data!", 400);
    }
    
    
    /**
     * Generate loris url based on the repoid and passing it back to the iiif template
     * 
     * @param string $repoid -> repoid
     * @return array
     */
    public function repo_iiif_viewer(string $repoid) : array
    {
        //RepoResource->getDissServ()['rawIIIf']->getUrl() -> when it is ready
        $basic = array();
        $lorisUrl = '';
        
        $repoUrl = $this->repo->getBaseUrl().$repoid;
        $result = array();
        $result = $this->generateView($repoid, 'iiif');
        if(isset($result['lorisUrl']) && !empty($result['lorisUrl']))
        {
           $lorisUrl = $result['lorisUrl'];
        }
        $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        
        return
            array(
                '#theme' => 'acdh-repo-ds-iiif-viewer',
                '#basic' => $basic,
                '#cache' => ['max-age' => 0], 
                '#lorisUrl' => $lorisUrl
            );
    }
    
     
    /**
     * Display the 3d object (nxs, ply) inside a js viewer
     * 
     * @param string $repoid -> repoid only
     * @return array
     */
    public function repo_3d_viewer(string $repoid) : array
    {   
        $basic = array();
        $result = array();
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl().$repoid;
            $result = $this->generateView($repoid, '3d');
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        
            if(count($result) > 0 && isset($result['result'])) {
                $result = $result['result'];
            }
        }
        return
            array(
                '#theme' => 'acdh-repo-ds-3d-viewer',
                '#ObjectUrl' => $result,
                '#cache' => ['max-age' => 0], 
                '#basic' => $basic
            );
    }
    
    
    
    
    
}
