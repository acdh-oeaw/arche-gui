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
     * The collection view GUI view with the metadata and the js treeview
     *
     * @param string $repoid
     * @return type
     */
    public function repo_dl_collection_view(string $repoid): array
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
        
        if (!isset($result['result'])) {
            $result['result'] = "";
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
    
    public function repo_3d_viewer_v2(string $repoid): array
    {
        $basic = array();
        $result = array();
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl() . $repoid;
            $result = $this->generateView($repoid, '3d');
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        }
        
        if (!isset($result['result'])) {
            $result['result'] = "";
        }
        
        return
                array(
                    '#theme' => 'acdh-repo-ds-3d-viewer-v2',
                    '#ObjectUrl' => $result['result'],
                    '#error' => $result['error'],
                    '#cache' => ['max-age' => 0],
                    '#basic' => $basic,
                    '#attached' => [
                        'library' => [
                            'acdh_repo_gui/ds-3d-viewer-kovacs-styles',
                        ]
                    ]
        );
    }
    
    public function repo_3d_viewer_v3(string $repoid): array
    {
        $basic = array();
        $result = array();
        if (!empty($repoid)) {
            $repoUrl = $this->repo->getBaseUrl() . $repoid;
            $result = $this->generateView($repoid, '3d');
            $basic = $this->detailViewController->generateObjDataForDissService($repoUrl);
        }
        
        if (!isset($result['result'])) {
            $result['result'] = "";
        }
        
        return
                array(
                    '#theme' => 'acdh-repo-ds-3d-viewer-v3',
                    '#ObjectUrl' => $result['result'],
                    '#error' => $result['error'],
                    '#cache' => ['max-age' => 0],
                    '#basic' => $basic,
                    '#attached' => [
                        'library' => [
                            'acdh_repo_gui/ds-3d-viewer-v3-styles',
                        ]
                    ]
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
