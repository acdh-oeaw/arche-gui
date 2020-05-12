<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of DashBoardController
 *
 * @author norbertczirjak
 */
class DashboardController extends ControllerBase {
    
    private $data = array();
    private $repo;
    private $model;
    
    public function __construct($repo) {
        //the main repo object
        $this->repo = $repo;
        //setup the dashboard model class
        $this->model = new \Drupal\acdh_repo_gui\Model\DashboardModel();        
        
        //check the site language
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";        
    }
    
    public function generateView(): array {
        
        //get the data from the DB
        $this->data = $this->model->getViewData();
        //pass the DB result to the Object generate functions
        
        return $this->data;
    }
}
