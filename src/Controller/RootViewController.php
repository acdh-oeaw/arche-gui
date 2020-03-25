<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\acdh_repo_gui\Model\RootViewModel;
use Drupal\acdh_repo_gui\Helper\RootViewHelper;
use Drupal\acdh_repo_gui\Helper\PagingHelper;


/**
 * Description of RootViewController
 *
 * @author nczirjak
 */
class RootViewController  extends ControllerBase {
    private $config;
    private $repo;
    private $model;
    private $helper;
    private $siteLang;
    private $numberOfRoots = 0;
    private $pagingHelper;
    
    public function __construct($repo) {
        $this->repo = $repo;
        $this->model = new RootViewModel();        
        $this->helper = new RootViewHelper();
        $this->pagingHelper = new PagingHelper();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    public function countRoots(): int {
        //count the actual root values
        $this->numberOfRoots = 0;
        $this->numberOfRoots = $this->model->countRoots($this->siteLang);
        //if we dont have root elements then we will send back an empty array
        return (int)$this->numberOfRoots;
    }
    
    public function generateRootView(string $limit = "10", string $page = "0", string $order = "datedesc"): array {
        if($this->numberOfRoots == 0){
            $this->numberOfRoots = $this->countRoots();
        }
        
        $data = $this->model->getViewData($limit, $page, $order, $this->siteLang);
        if(count((array)$data) == 0) {
            echo "no data";
        }
        
        
        $numPage = ceil((int)$this->numberOfRoots / (int)$limit);
        //change the page and offset variables, because we want the paging to start from 1 not 0
        ($page == 0) ? $page = 1 : "";
        ($page == 1) ? $offset = 0 : $offset = ($page -1) * $limit;
        $pagination = '';
        $pagination = $this->pagingHelper->createView(
            array(
                'limit' => $limit, 'page' => $page, 'order' => $order,
                'numPage' => $numPage, 'sum' => $this->numberOfRoots
            )
        );
        
        return array('data' => $this->helper->createView($data), 'pagination' => $pagination);
    }
}
