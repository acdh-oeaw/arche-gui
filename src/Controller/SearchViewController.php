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
class SearchViewController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $pagingHelper;

    public function __construct()
    {
        parent::__construct();
        $this->model = new SearchViewModel();
        $this->helper = new SearchViewHelper();
        $this->pagingHelper = new PagingHelper();
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

        //for the DB we need a 0
        ((int) $page == 1) ? (int) $page = 0 : $page = (int) $page;
        $data = $this->model->getViewData($limit, $page, $order, $this->helper->createMetaObj($metavalue));

        if (isset($data['count']) && $data['count'] > 0) {
            $numPage = ceil((int) $data['count'] / (int) $limit);
            /// for the gui pager we need 1 for the first page
            ((int) $page == 0) ? (int) $page = 1 : $page = (int) $page;

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
        ((int) $page == 1) ? (int) $page = 0 : $page = (int) $page;
        $data = $this->model->getViewData_V2($limit, $page, $order, $metaobj);

        if (isset($data['count']) && $data['count'] > 0) {
            $numPage = ceil((int) $data['count'] / (int) $limit);
            /// for the gui pager we need 1 for the first page
            ((int) $page == 0) ? (int) $page = 1 : $page = (int) $page;
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

    public function rest_search(string $metavalue = "root")
    {
        $url = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/search?operator[]=@@&value[]=Ressourcen&readMode=resource';
        $urlroot= 'https://arche-dev.acdh-dev.oeaw.ac.at/api/search?operator[]=@@'
                . '&property[]=http://www.w3.org/1999/02/22-rdf-syntax-ns%23type'
                . '&value[]=https://vocabs.acdh.oeaw.ac.at/schema%23TopCollection'
                . '&readMode=resource&format=application/json';
        
        $connection = new \Drupal\acdh_repo_gui\Helper\ArcheRestConnection();
       
        $response = $connection->callEndpoint($url, [
                'limit' => 10,
                'offset' => 0,
                'url_query' => [
                    'sort' => 'title',
                ]
            ]);
            
            
            
            
        echo '<pre>';
        var_dump($response);
        echo '</pre>';
        die();
            
        $json = json_decode($response->getBody());
            
        echo '<pre>';
        var_dump($response->getBody());
        echo '</pre>';
        die();
    }
}
