<?php

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
class SearchViewController extends ControllerBase
{
    private $repo;
    private $model;
    private $helper;
    private $numberOfResults;
    
    public function __construct($repo)
    {
        $this->repo = $repo;
        $this->model = new SearchViewModel();
        $this->helper = new SearchViewHelper();
        $this->pagingHelper = new PagingHelper();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    public function generateView(int $limit = 10, int $page = 0, string $order = 'datedesc', string $metavalue = ''): array
    {
        $metaobj = new \stdClass();
        $metaobj = $this->helper->createMetaObj($metavalue);
        
        $data = $this->model->getViewData($limit, $page, $order, $metaobj);
        
        $numPage = ceil((int)$data['count'] / (int)$limit);
        
        $pagination = '';
        $pagination = $this->pagingHelper->createView(
            array(
                'limit' => $limit, 'page' => $page, 'order' => $order,
                'numPage' => $numPage, 'sum' => $data['count']
            )
        );
        
        return array('data' => $this->helper->createView($data['data']), 'pagination' => $pagination);
    }
}
