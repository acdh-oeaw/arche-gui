<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Model\SearchViewModel;
use Drupal\acdh_repo_gui\Helper\SearchViewHelper;
use Drupal\acdh_repo_gui\Helper\PagingHelper;

/**
 * Description of SearchViewController
 *
 * @author nczirjak
 */
class SearchViewController extends \Drupal\Core\Controller\ControllerBase
{
    private $repo;
    private $config;
    private $model;
    private $helper;
    private $pagingHelper;
    
    public function __construct()
    {
        $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        $this->model = new SearchViewModel();
        $this->helper = new SearchViewHelper();
        $this->pagingHelper = new PagingHelper();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    
    /**
     * Full text search version 2
     * @param string $metavalue
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return array
     */
    public function generateView(string $metavalue = "root", string $limit = "10", string $page = "0", string $order = "titleasc"): array
    {
        $data = array();
        $guiData = array();
        $metaobj = new \stdClass();
        $metaobj = $this->helper->createMetaObj($metavalue);
        
        //for the DB we need a 0
        ((int)$page == 1) ? (int)$page = 0: $page = (int)$page;
        $data = $this->model->getViewData($limit, $page, $order, $metaobj);
        
        if (isset($data['count']) && $data['count'] > 0) {
            $numPage = ceil((int)$data['count'] / (int)$limit);
            /// for the gui pager we need 1 for the first page
            ((int)$page == 0) ? (int)$page = 1: $page = (int)$page;
            $pagination = '';
            $pagination = $this->pagingHelper->createView(
                array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => $numPage, 'sum' => $data['count']
                )
            );
            
            $guiData = array('data' => $this->helper->createView($data['data'], 2), 'pagination' => $pagination);
           
        } else {
            $guiData['data'] = array();
            $guiData['pagination'] = $this->pagingHelper->createView(
                array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => 1, 'sum' => 0
                )
            );
        }
       
        return [
            '#theme' => 'acdh-repo-gui-search-full',
            '#data' => $guiData['data'],
            '#paging' => $guiData['pagination'][0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }
    
    /**
     * New fulltext search with binary search
     *
     * @param string $metavalue
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return array
    */
    public function fulltext_search(string $metavalue = "root", string $limit = "10", string $page = "0", string $order = "titleasc"): array
    {
        $data = array();
        $guiData = array();
        $metaobj = new \stdClass();
        $metaobj = $this->helper->createMetaObj($metavalue);
       
        //for the DB we need a 0
        ((int)$page == 1) ? (int)$page = 0: $page = (int)$page;
        $data = $this->model->getViewData_V2($limit, $page, $order, $metaobj);
        
        if (isset($data['count']) && $data['count'] > 0) {
            $numPage = ceil((int)$data['count'] / (int)$limit);
            /// for the gui pager we need 1 for the first page
            ((int)$page == 0) ? (int)$page = 1: $page = (int)$page;
            $pagination = '';
            $pagination = $this->pagingHelper->createView(
                array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => $numPage, 'sum' => $data['count']
                )
            );

            $guiData = array('data' => $this->helper->createView($data['data']), 'pagination' => $pagination);
        } else {
            $guiData['data'] = array();
            $guiData['pagination'] = $this->pagingHelper->createView(
                array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => 1, 'sum' => 0
                )
            );
        }
               
        return [
            '#theme' => 'acdh-repo-gui-search-full',
            '#data' => $guiData['data'],
            '#paging' => $guiData['pagination'][0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }
}
