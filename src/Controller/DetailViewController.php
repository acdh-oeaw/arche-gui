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
    private $repoId;
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
        $this->repoid = str_replace($this->repo->getBaseUrl(), '', $identifier);
        $dv = array();
        $dv = $this->model->getViewData($this->repoUrl);
        
        $breadcrumb = array();
        $breadcrumb = $this->model->getBreadCrumbData($this->repoid);
        if(count((array)$dv) == 0) {
            return new \stdClass();
        }
        
        //extend the data object with the shortcuts
        $this->basicViewData = new \stdClass();
        $this->basicViewData->basic = $this->helper->createView($dv);
        $this->basicViewData->basic = $this->basicViewData->basic[0];
        
        // check the dissemination services
        if(isset($dv[0]->id) && !is_null($dv[0]->id)) {
            $this->basicViewData->dissemination = $this->generalFunctions->getDissServices($dv[0]->id);
        }
        
        //get the cite widget data
        $cite = new CH($this->repo);
        $this->basicViewData->extra = new \stdClass();
        $this->basicViewData->extra->citeWidgetData = $cite->createCiteThisWidget($this->basicViewData->basic);
        if(count((array)$breadcrumb) > 0) {
            $this->basicViewData->extra->breadcrumb = $breadcrumb;
        } 
        
        return $this->basicViewData;
    }
    
    /**
     * 
     * generate the basic metadata for the root resource/collection in the dissemination services view
     * 
     * @param string $identifier -> full repoUrl
     * @return object
     */
    public function generateObjDataForDissService(string $identifier): object {
        $dv = array();
        $dv = $this->model->getViewData($identifier);
        
        if(count((array)$dv) == 0) {
            return new \stdClass();
        } 
       
        //extend the data object with the shortcuts
        $obj = new \stdClass();
        $obj = $this->helper->createView($dv);
        if(isset($obj[0])) {
            return $obj[0];
        }
        return new \stdClass();
    }
    
}
