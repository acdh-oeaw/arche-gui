<?php

namespace Drupal\acdh_repo_gui\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\acdh_repo_gui\Model\DisseminationServicesModel;
use Drupal\acdh_repo_gui\Helper\DisseminationServicesHelper;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Controller\DetailViewController as DVC;

/**
 * Description of DisseminationServicesController
 *
 * @author norbertczirjak
 */
class DisseminationServicesController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $basicViewData;
    private $generalFunctions;
    private $detailViewController;
    private $disseminations = array(
        'collection', '3d', 'iiif', 'collection_lazy'
    );

    public function __construct()
    {
        parent::__construct();
        $this->model = new DisseminationServicesModel();
        $this->helper = new DisseminationServicesHelper($this->repo);
        $this->generalFunctions = new GeneralFunctions();
        $this->detailViewController = new DVC($this->repo);
    }

    /**
     *
     * @param string $identifier
     * @param string $dissemination
     * @param array $additionalData
     * @return array
     */
    public function generateView(string $identifier, string $dissemination, array $additionalData = array()): array
    {
        if (empty($identifier) || !in_array($dissemination, $this->disseminations)) {
            return array();
        }

        $vd = array();
        $vd = $this->model->getViewData($identifier, $dissemination);
        if (count((array) $vd) == 0 && ($dissemination == "collection" || $dissemination == "collection_lazy")) {
            return array("id" => 0, "title" => $this->t('No child element'), "text" => $this->t('No child element'));
        }

        return $this->basicViewData = $this->helper->createView($vd, $dissemination, $identifier, $additionalData);
    }

    /**
     * get the collection binaries
     * @param string $repoid
     * @return Response
     */
    public function repo_dl_collection_binaries(string $repoid): Response
    {
        $GLOBALS['resTmpDir'] = "";
        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        //the binary files
        $binaries = $this->generalFunctions->jsonDecodeData($_POST['jsonData']);

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
     * This generates the jstree data for the collection download view
     *
     * @param string $repoid
     * @return Response
     */
    public function repo_get_collection_data(string $repoid): Response
    {
        $result = array();
        $repoBaseObj = new \stdClass();
        $rootTitle = '';
        if (empty($repoid)) {
            $errorMSG = t('Missing') . ': Identifier';
        } else {
            //get the root collection data
            $repourl = $this->generalFunctions->detailViewUrlDecodeEncode($repoid, 0);
            $repoBaseObj = $this->detailViewController->generateObjDataForDissService($repourl);
            if ((count((array) $repoBaseObj) > 0) && ($repoBaseObj->getTitle() !== null)) {
                $rootTitle = $repoBaseObj->getTitle();
            }

            $result = $this->generateView($repoid, 'collection', array('title' => $rootTitle));
        }

        $response = new Response();
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function get_collection_data_lazy(string $id): Response
    {
        $result = array();
        $repoBaseObj = new \stdClass();
        $rootTitle = '';
        if (empty($id)) {
            $errorMSG = t('Missing') . ': Identifier';
        } else {
            //get the root collection data
            $repourl = $this->generalFunctions->detailViewUrlDecodeEncode($id, 0);
            $repoBaseObj = $this->detailViewController->generateObjDataForDissService($repourl);
            if ((count((array) $repoBaseObj) > 0) && ($repoBaseObj->getTitle() !== null)) {
                $rootTitle = $repoBaseObj->getTitle();
            }
            $result = $this->generateView($id, 'collection_lazy', array('title' => $rootTitle));
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
    public function repo_dl_collection_view(string $repoid)
    {
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
        if (empty($repoid)) {
            $result = '';
        } else {
            $repoid = $this->repo->getBaseUrl() . $repoid;
            $result = $this->generalFunctions->changeCollDLScript($repoid);
        }

        $response = new Response();
        $response->setContent($result);
        $response->headers->set('Content-Type', 'application/x-python-code');
        $response->headers->set('Content-Disposition', 'attachment; filename=collection_download_script.py');
        return $response;
    }

    /**
     * Generate loris url based on the repoid and passing it back to the iiif template
     *
     * @param string $repoid -> repoid
     * @return array
     */
    public function repo_iiif_viewer(string $repoid): array
    {
        //RepoResource->getDissServ()['rawIIIf']->getUrl() -> when it is ready
        $lorisUrl = '';

        $repoUrl = $this->repo->getBaseUrl() . $repoid;
        $result = $this->generateView($repoid, 'iiif');
        if (isset($result['lorisUrl']) && !empty($result['lorisUrl'])) {
            $lorisUrl = $result['lorisUrl'];
        }
        $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);

        return
                array(
                    '#theme' => 'acdh-repo-ds-iiif-viewer',
                    '#basic' => $basic,
                    '#cache' => ['max-age' => 0],
                    '#lorisUrl' => $lorisUrl,
                    '#attached' => [
                        'library' => [
                            'acdh_repo_gui/ds-iiif-viewer-styles',
                        ]
                    ]
        );
    }

    /**
     * Display the 3d object (nxs, ply) inside a js viewer
     *
     * @param string $repoid -> repoid only
     * @return array
     */
    public function repo_3d_viewer(string $repoid): array
    {
        $basic = array();
        $result = array();
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl() . $repoid;
            $result = $this->generateView($repoid, '3d');
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        }
        
        return
                array(
                    '#theme' => 'acdh-repo-ds-3d-viewer',
                    '#ObjectUrl' => $result['result'],
                    '#error' => $result['error'],
                    '#cache' => ['max-age' => 0],
                    '#basic' => $basic                    
        );
    }

    /**
     * Display PDF in viewer
     * @param string $repoid
     * @return array
     */
    public function repo_pdf_viewer(string $repoid): array
    {
        $basic = array();
        $repoUrl = "";
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl() . $repoid;
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        }

        return
                array(
                    '#theme' => 'acdh-repo-ds-pdf-viewer',
                    '#ObjectUrl' => $repoUrl,
                    '#cache' => ['max-age' => 0],
                    '#basic' => $basic,
                    '#attached' => [
                        'library' => [
                            'acdh_repo_gui/ds-pdf-styles',
                        ]
                    ]
        );
    }
}
