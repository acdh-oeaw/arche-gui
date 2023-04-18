<?php

namespace Drupal\acdh_repo_gui\Controller;

/**
 * Description of RootViewController
 *
 * @author nczirjak
 */
class RootViewController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    public function __construct()
    {
        parent::__construct();
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
       
        //we have to pass the template for the view and the js will handle the others
        return [
            '#theme' => 'acdh-repo-gui-main',
            '#data' => [],
            '#paging' => "",
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-root-view',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }
}
