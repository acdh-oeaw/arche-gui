<?php

/**
 * @file
 * Contains \Drupal\acdh_repo_gui\Plugin\Block\SearchSBBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Version' block.
 *
 * @Block(
 *   id = "repo_versions_block",
 *   admin_label = @Translation("Versions"),
 *   category = @Translation("ARCHE Resource Versions Block")
 * )
 */
class ArcheVersionsBlock extends BlockBase {

    /**
     * Search Sb block
     *
     * @return type
     */
    public function build() {
        \Drupal::service('page_cache_kill_switch')->trigger(); 
        
        $data = array();
        $id = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (!empty($id)) {
            $controller = new \Drupal\acdh_repo_gui\Controller\VersionsController();
            $data = $controller->generateView($id);
        }
        
        if(count($data) < 1) {
            $data = array();
        }

        return [
            '#theme' => 'acdh-repo-gui-detail-versions-block',
            '#result' => $data
        ];
    }

    /**
     * @return int
     */
    public function getCacheMaxAge() {
        return 0;
    }

}
