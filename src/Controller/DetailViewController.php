<?php

namespace Drupal\acdh_repo_gui\Controller;

use Symfony\Component\HttpFoundation\Response;
use Drupal\acdh_repo_gui\Model\DetailViewModel;
use Drupal\acdh_repo_gui\Helper\DetailViewHelper;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions as GF;

/**
 * Description of DetailViewController
 *
 * @author nczirjak
 */
class DetailViewController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $basicViewData;
    private $repoUrl;
    private $repoid;
    private $generalFunctions;

    public function __construct()
    {
        parent::__construct();
        $this->model = new DetailViewModel();
        $this->helper = new DetailViewHelper($this->config);
        $this->generalFunctions = new GF();
    }

    private function checkAjaxRequestIsOn(string $identifier): bool
    {
        if (strpos($identifier, '&ajax') !== false) {
            return true;
        }
        return false;
    }

    private function getIdentifierFromAjax(string $identifier): string
    {
        if (strpos($identifier, '&ajax') !== false) {
            $identifier = explode('&', $identifier);
            return $identifier[0];
        }
        return '';
    }

    /**
     * the detail view
     *
     * @param string $identifier
     * @return type
     */
    public function detailViewMainMethod(string $identifier)
    {
        $ajax = $this->checkAjaxRequestIsOn($identifier);
        if ($ajax) {
            $identifier = $this->getIdentifierFromAjax($identifier);
        }

        $dv = array();
        $identifier = $this->generalFunctions->detailViewUrlDecodeEncode($identifier, 0);
        $dv = $this->generateDetailView($identifier);

        if (count((array) $dv) < 1) {
            \Drupal::messenger()->addWarning(t('You do not have data'));
            return array();
        }

        \Drupal::service('page_cache_kill_switch')->trigger();
       
        $dv->extra->clarinVCRUrl = $this->generalFunctions->initClarinVcrUrl();
      
        $return = [
            '#theme' => 'acdh-repo-gui-detail',
            '#basic' => $dv->basic,
            '#extra' => $dv->extra,
            '#dissemination' => (isset($dv->dissemination)) ? $dv->dissemination : array(),
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ]
        ];
        if ($ajax) {
            return new Response(render($return));
        }
        return $return;
    }

    

    /**
     * set up the breadcrumb data
     * @return void
     */
    private function setBreadcrumb(): void
    {
        $this->basicViewData->extra->breadcrumb = new \Drupal\acdh_repo_gui\Object\BreadCrumbObject($this->model->getBreadCrumbData($this->repoid));
    }

    /**
     * Generate the detail view
     *
     * @param string $identifier
     * @return type
     */
    private function generateDetailView(string $identifier): object
    {
        $this->repoUrl = $identifier;
        //remove the url from the identifier just to have the repoid
        $this->repoid = str_replace($this->repoDb->getBaseUrl(), '', $identifier);
        $dv = [];

        //get the detail view raw data from the database
        $dv = $this->model->getViewData($this->repoUrl);

        if (count((array) $dv) == 0) {
            return new \stdClass();
        }
        $this->basicViewData = new \stdClass();
        $this->basicViewData->extra = new \stdClass();
        $this->setBreadcrumb();

        //extend the data object with the shortcuts
        $this->basicViewData->basic = $this->helper->createView($dv);
        $this->basicViewData->basic = $this->basicViewData->basic[0];

        // check the dissemination services
        if (isset($dv[0]->id) && !is_null($dv[0]->id)) {
            $this->basicViewData->dissemination = $this->generalFunctions->getDissServices($dv[0]->id);
        }
        
        $this->setToolTip();
        
        //get the child view data, if we dont have any arg in the url, then the ajax call will handle the child views
        $path = \Drupal::request()->getpathInfo();
        if (strpos($path, '/detail/') !== false && strpos($path, '&page=') === false && strpos($path, '&order=') === false && strpos($path, '&limit=') === false) {
            $this->basicViewData->extra->childData = $this->getChildData();
        }
        
        return $this->basicViewData;
    }

    /**
     * Set up tooltip data
     * @return void
     */
    private function setToolTip(): void
    {
        //get the tooltip
        $tooltip = $this->model->getTooltipOntology();
        if (count($tooltip) > 0) {
            $this->basicViewData->extra->tooltip = new \Drupal\acdh_repo_gui\Object\ToolTipObject($tooltip);
        }
    }

    private function setToolTipApi(): object
    {
        //get the tooltip
        $tooltip = $this->model->getTooltipOntology();
        if (count($tooltip) > 0) {
            return new \Drupal\acdh_repo_gui\Object\ToolTipObject($tooltip);
        }
        return new \stdClass();
    }

    /**
     * Generate the basic metadata for the root resource/collection in the dissemination services view
     *
     * @param string $identifier -> full repoUrl
     * @return object
     */
    public function generateObjDataForDissService(string $identifier): object
    {
        $dv = array();
        $dv = $this->model->getViewData($identifier);

        if (count((array) $dv) == 0) {
            return new \stdClass();
        }

        //extend the data object with the shortcuts
        $obj = new \stdClass();
        $obj = $this->helper->createView($dv);
        if (isset($obj[0])) {
            return $obj[0];
        }
        return new \stdClass();
    }

    /**
     * Get the child view data
     * @return \Symfony\Component\HttpFoundation\Response
     */
    private function getChildData(): \Symfony\Component\HttpFoundation\Response
    {
        $child = new \Drupal\acdh_repo_gui\Controller\ChildApiController();
        return $child->generateView($this->repoid, '10', '0', 'titleasc');
    }
    
    
    
    /**
     * Detail view for the API Call template
     * @param string $identifier
     * @return array
     */
    public function detailMain(string $identifier): array
    {
        $id = $identifier;
        //we have ti handle hier the handle urls and not just the IDs!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!

        $identifier = $this->generalFunctions->detailViewUrlDecodeEncode($identifier, 0);
        
        return $return = [
            '#theme' => 'acdh-repo-gui-detail-api',
            '#helper' => array("identifier" => $identifier, 'acdhid' => $id),
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-detail-api',
                ]
            ]
        ];
    }
    
    /**
     * Detail view template for the generated API call results.
     * @param string $identifier
     * @param string $lang
     * @return Response
     */
    public function detailOverviewApi(string $identifier, string $lang): Response
    {
        $id = $identifier;
        //we have ti handle hier the handle urls and not just the IDs!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
        
        $identifier = $this->generalFunctions->detailViewUrlDecodeEncode($identifier, 0);
        
        $result = [];
        $result = $this->helper->overviewObj($id);
       
        $diss = $this->generalFunctions->getDissServices($id);
       
        if (count($result) === 0) {
            return new Response(\json_encode(array("There is no data")), 404, ['Content-Type' => 'application/json']);
        }
        
        $build = [
            '#theme' => 'arche-detail-overview',
            '#basic' => $result,
            '#tooltip' => $this->setToolTipApi(),
            '#dissemination' => $diss,
            '#clarinVCRUrl' => $this->generalFunctions->initClarinVcrUrl(),
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-detail-api',
                ]
            ]
        ];

        
        return new Response(render($build));
    }
}
