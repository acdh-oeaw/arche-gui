<?php

namespace Drupal\acdh_repo_gui\Controller;

/**
 * Description of RootViewController
 *
 * @author nczirjak
 */
class RootViewController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $numberOfRoots = 0;
    private $pagingHelper;
    private $generalFunctions;

    public function __construct()
    {
        parent::__construct();
        $this->model = new \Drupal\acdh_repo_gui\Model\RootViewModel();
        $this->helper = new \Drupal\acdh_repo_gui\Helper\RootViewHelper();
        $this->generalFunctions = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions();
        $this->pagingHelper = new \Drupal\acdh_repo_gui\Helper\PagingHelper();
    }

    public function countRoots()
    {
        //count the actual root values
        $this->numberOfRoots = 0;
        $this->numberOfRoots = $this->model->countRoots($this->siteLang);
    }

    /**
     * Generate the main root view
     * @param string $order
     * @param string $limit
     * @param string $page
     * @return array
     */
    public function generateView(string $order = "datedesc", string $limit = "10", string $page = "1"): array
    {
        $limit = (int) $limit;
        $page = (int) $page;
        // on the gui we are displaying 1 as the first page.
        //$page = $page-1;
        $this->countRoots();

        $roots = array();
        $paging = array();
        if ((int) $this->numberOfRoots > 0) {
            $roots = $this->generateRootViewData((int) $limit, (int) $page, $order);
        }

        if (!isset($roots['data']) || count($roots['data']) <= 0) {
            \Drupal::messenger()->addWarning($this->t('You do not have Root resources'));
            return array();
        }

        if (count($roots['pagination']) > 0) {
            $paging = $roots['pagination'][0];
        }

        return [
            '#theme' => 'acdh-repo-gui-main',
            '#data' => $roots['data'],
            '#paging' => $paging,
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-root-view',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }

    /**
     * Generate the data for the root views (main/front page left block)
     * @param int $limit
     * @param int $page
     * @param string $order
     * @return array
     */
    public function generateRootViewData(int $limit = 10, int $page = 0, string $order = "datedesc"): array
    {
        $data = $this->model->getViewData($limit, $page, $order);
        if (count((array) $data) == 0) {
            return array();
        }

        $numPage = ceil((int) $this->numberOfRoots / (int) $limit);

        $pagination = $this->pagingHelper->createView(
            array(
                    'limit' => $limit, 'page' => $page, 'order' => $order,
                    'numPage' => $numPage, 'sum' => $this->numberOfRoots
                )
        );
        return array('data' => $this->helper->createView($data), 'pagination' => $pagination);
    }
}
