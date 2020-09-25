<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\acdh_repo_gui\Model\DetailViewModel;
use Drupal\acdh_repo_gui\Helper\DetailViewHelper;
use Drupal\acdh_repo_gui\Helper\CiteHelper as CH;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions as GF;

/**
 * Description of DetailViewController
 *
 * @author nczirjak
 */
class DetailViewController extends ControllerBase {

    private $config;
    private $model;
    private $helper;
    private $basicViewData;
    private $repoUrl;
    private $repoid;
    private $generalFunctions;

    public function __construct($repo) {
        $this->repo = $repo;
        $this->model = new DetailViewModel();
        $this->helper = new DetailViewHelper($this->config);
        $this->generalFunctions = new GF();
    }

    /**
     * Generate the detail view
     *
     * @param string $identifier
     * @return type
     */
    public function generateDetailView(string $identifier): object {
        $this->repoUrl = $identifier;
        //remove the url from the identifier just to have the repoid
        $this->repoid = str_replace($this->repo->getBaseUrl(), '', $identifier);
        $dv = array();
        //get the detail view raw data from the database
        $dv = $this->model->getViewData($this->repoUrl);

        $breadcrumb = array();
        $breadcrumb = $this->model->getBreadCrumbData($this->repoid);
        if (count((array) $dv) == 0) {
            return new \stdClass();
        }

        //extend the data object with the shortcuts
        $this->basicViewData = new \stdClass();
        $this->basicViewData->basic = $this->helper->createView($dv);
        $this->basicViewData->basic = $this->basicViewData->basic[0];
        $this->basicViewData->extra = new \stdClass();

        //add the breadcrumb to the final results
        if (count((array) $breadcrumb) > 0) {
            $this->basicViewData->extra->breadcrumb = array();
            $this->basicViewData->extra->breadcrumb = $breadcrumb;
        }

        // check the dissemination services
        if (isset($dv[0]->id) && !is_null($dv[0]->id)) {
            $this->basicViewData->dissemination = $this->generalFunctions->getDissServices($dv[0]->id);
        }

        if (in_array($this->basicViewData->basic->getAcdhType(),
                        array("Collection", "Project", "Resource", "Publication", "Metadata"))
        ) {
            //get the cite widget data
            $cite = new CH($this->repo, $this->basicViewData->basic);

            if (in_array($this->basicViewData->basic->getAcdhType(),
                            array("Collection", "Project"))
            ) {
                $this->basicViewData->extra->citeWidgetData = $cite->createCiteWidgetCollectionProject();
            } else {
                //top collection data
                $tc = array();
                $tcObj = new \stdClass();

                //we need the top collection data for the  cite data
                if (isset($this->basicViewData->extra->breadcrumb[0]->parentid)) {
                    $tcm = array();
                    $tcm = $this->model->getViewData($this->repo->getBaseUrl() . $this->basicViewData->extra->breadcrumb[0]->parentid);
                                       
                    if (count($tcm) > 0) {
                       
                        $tc = array();
                        $tc = $this->helper->createView($tcm);
                       
                        //we have view data 
                        if (count($tc) > 0 && isset($tc[0])) {
                            $tcObj = $tc[0];
                        }
                    }
                }
                
                $this->basicViewData->extra->citeWidgetData = $cite->createCiteWidgetResourceMetadata($tcObj);
            }
        }

        //get the tooltip
        $tooltip = array();
        $tooltip = $this->model->getTooltipOntology();
        if (count($tooltip) > 0) {
            $tooltip = $this->helper->formatTooltip($tooltip);
            $this->basicViewData->extra->tooltip = $tooltip;
        }

        //get the child view data, if we dont have any arg in the url, then the ajax call will handle the child views
        $path = \Drupal::request()->getpathInfo();
        if (strpos($path, '/oeaw_detail/') !== false && strpos($path, '&page=') === false && strpos($path, '&order=') === false && strpos($path, '&limit=') === false) {
            $this->basicViewData->extra->childData = $this->getChildData();
        }

        return $this->basicViewData;
    }

    /**
     * Generate the basic metadata for the root resource/collection in the dissemination services view
     *
     * @param string $identifier -> full repoUrl
     * @return object
     */
    public function generateObjDataForDissService(string $identifier): object {
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
    private function getChildData(): \Symfony\Component\HttpFoundation\Response {
        $child = new \Drupal\acdh_repo_gui\Controller\ChildApiController();
        return $child->repo_child_api($this->repoid, '10', '0', 'titleasc');
    }

}
