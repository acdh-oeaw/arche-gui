<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use acdhOeaw\arche\lib\Repo;
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
        $guiData['data'] = array();
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
     * Get the json object with the search values for the VCR submit
     * @param string $metavalue
     * @return Response
     */
    public function search_vcr(string $metavalue = "root"): \Symfony\Component\HttpFoundation\Response
    {
        $this->modelData = $this->model->getVcr($this->helper->createMetaObj($metavalue));
        
        if(count($this->modelData) > 0 && isset($this->modelData[0]->json_agg)) {
            
            return new \Symfony\Component\HttpFoundation\Response($this->modelData[0]->json_agg, 200, ['Content-Type' => 'application/json']);
        }
        return new JsonResponse(array("There is no data"), 404, ['Content-Type' => 'application/json']);
    }
}
