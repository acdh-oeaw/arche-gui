<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\acdh_repo_gui\Model\SearchViewModel;
use Drupal\acdh_repo_gui\Helper\SearchViewHelper;
use Drupal\acdh_repo_gui\Helper\PagingHelper;
/**
 * Description of SearchViewController
 *
 * @author nczirjak
 */
class SearchViewController extends ControllerBase {
    
    private $repo;
    private $model;
    private $helper;
    
    public function __construct($repo) {
        $this->repo = $repo;
        $this->model = new SearchViewModel();        
        $this->helper = new SearchViewHelper();
        $this->pagingHelper = new PagingHelper();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    public function generateView(int $limit = 10, int $page = 0, string $order = 'datedesc', string $metavalue = ''): array {
        $data = $this->model->getViewData($limit, $page, $order, '');
        
        $pagination = '';
        return array('data' => $this->helper->createView($data), 'pagination' => $pagination);
    }
    
}
