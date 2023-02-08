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
    private $searchParams = [];

    public function __construct()
    {
        parent::__construct();
        $this->model = new SearchViewModel();
        $this->helper = new SearchViewHelper();
        $this->pagingHelper = new PagingHelper();
    }

    public function generateView(string $metavalue): array
    {
        $this->searchParams = $this->helper->paramsToSqlParams($metavalue);
        
        // = "root", string $limit = "10", string $page = "0", string $order = "titleasc"
        $data = array();
        $guiData = array();
        $guiData['data'] = array();
        $guiData['extra'] = array();
        //for the DB we need a 0
        
        $data = $this->model->getViewData($this->searchParams);

        if (isset($data['count']) && $data['count'] > 0) {
            $numPage = ceil((int) $data['count'] / (int) $this->searchParams['limit'][0]);
            /// for the gui pager we need 1 for the first page
            ((int) $this->searchParams['page'][0] == 0) ? (int) $this->searchParams['page'][0] = 1 : $this->searchParams['page'][0] = (int) $this->searchParams['page'][0];

            $pagination = $this->pagingHelper->createView(
                array(
                        'limit' => $this->searchParams['limit'][0], 'page' => $this->searchParams['page'][0], 'order' => $this->searchParams['order'][0],
                        'numPage' => $numPage, 'sum' => $data['count']
                    )
            );

            $guiData = array('data' => $this->helper->createView($data['data'], 2), 'pagination' => $pagination);
            $guiData['extra']['baseUrl'] = $this->repo->getBaseUrl();
            $ge = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions();
            $guiData['extra']['clarinUrl'] = $ge->initClarinVcrUrl();
        } else {
            $guiData['data'] = array();
            $guiData['pagination'] = $this->pagingHelper->createView(
                array(
                        'limit' => $this->searchParams['limit'][0], 'page' => $this->searchParams['page'][0], 'order' => $this->searchParams['order'][0],
                        'numPage' => 1, 'sum' => 0
                    )
            );
        }

        return [
            '#theme' => 'acdh-repo-gui-search-full',
            '#data' => $guiData['data'],
            '#extra' => $guiData['extra'],
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
    public function search_vcr(string $metavalue): \Symfony\Component\HttpFoundation\Response
    {
        $this->modelData = $this->model->getVcr($this->helper->paramsToSqlParams($metavalue));
        if (count($this->modelData) > 0 && isset($this->modelData[0]->json_agg)) {
            return new \Symfony\Component\HttpFoundation\Response(\json_encode($this->modelData[0]->json_agg), 200, ['Content-Type' => 'application/json']);
        }
        return new Response(\json_encode(array("There is no data")), 404, ['Content-Type' => 'application/json']);
    }
}
