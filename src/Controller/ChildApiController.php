<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\acdh_repo_gui\Model\ChildApiModel;
use Drupal\acdh_repo_gui\Helper\ChildApiHelper;
use Drupal\acdh_repo_gui\Helper\PagingHelper;
use Symfony\Component\HttpFoundation\Response;

/**
 * Description of ChildApiController
 *
 * @author nczirjak
 */
class ChildApiController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $data;
    private $childNum;
    private $pagingHelper;
    private $repoid;
    private $identifier;

    public function __construct()
    {
        parent::__construct();
        $this->model = new ChildApiModel();
        $this->helper = new ChildApiHelper();
        $this->data = new \stdClass();
        $this->pagingHelper = new PagingHelper();
    }

    /**
     * This API will generate the child html view.
     *
     * @param string $identifier - the UUID
     * @param string $page
     * @param string $limit
     */
    public function generateView(string $identifier, string $limit, string $page, string $order): Response
    {
        $this->setupIdentifier($identifier);
        $this->model->getPropertiesByClass($this->repoid);
        $this->childNum = $this->model->getCount($this->identifier);

        if ($this->childNum < 1) {
            $this->data->errorMSG = $this->t('There are no Child resources');
            goto end;
        }

        $this->setupPagingVariables($limit, $page, $order);
        $this->data->acdhType = strtolower(str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $this->model->getAcdhtype()));

        $data = $this->model->getViewData($this->identifier, (int) $this->data->limit, (int) $this->data->offset, $this->data->order);

        $this->data->data = $this->helper->createView($data);
        if (count((array) $this->data->data) <= 0) {
            $this->data->errorMSG = $this->t('There are no Child resources');
        }

        end:

        $build = [
            '#theme' => 'acdh-repo-gui-child',
            '#data' => (array) $this->data,
            '#cache' => ['max-age' => 0]
        ];

        return new Response(render($build));
    }

    /**
     * setup the variables for the paging
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return void
     */
    private function setupPagingVariables(string $limit, string $page, string $order): void
    {
        $this->data->sum = $this->childNum;
        $this->data->limit = $limit;
        $this->data->page = $page;
        $this->data->order = $order;
        $this->data->numPage = ceil((int) $this->childNum / (int) $limit);

        //change the page and offset variables, because we want the paging to start from 1 not 0
        ($page == 0) ? $page = 1 : "";
        ($page == 1) ? $offset = 0 : $offset = ($page - 1) * $limit;

        $this->data->offset = $offset;

        $this->data->pagination = $this->pagingHelper->createView(
            array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => $this->data->numPage, 'sum' => $this->data->sum
                )
        );
        $this->data->pagination = $this->data->pagination[0];
    }

    /**
     * create the identifiers
     * @param string $identifier
     * @return void
     */
    private function setupIdentifier(string $identifier): void
    {
        if (preg_match("/^\d+$/", $identifier)) {
            $this->repoid = $identifier;
            $this->identifier = $this->repo->getBaseUrl() . $identifier;
        } elseif (strpos($identifier, $this->repo->getSchema()->namespaces->id.'uuid/') === false) {
            $this->repoid = $identifier;
            $this->identifier = $identifier;
        }
        $this->data->identifier = $this->identifier;
    }
}
