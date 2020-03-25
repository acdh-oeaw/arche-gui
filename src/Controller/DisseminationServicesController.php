<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\acdh_repo_gui\Model\DisseminationServicesModel;
use Drupal\acdh_repo_gui\Helper\DisseminationServicesHelper;

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
    
    private $disseminations = array(
        'collection', '3d', 'iiif', 'turtle_api'
    );
    
    public function __construct($repo) {
        $this->repo = $repo;
        $this->model = new DisseminationServicesModel();
        $this->helper = new DisseminationServicesHelper($this->repo);
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
    
    
    
}
