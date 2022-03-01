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
class ArcheVersionsBlock extends BlockBase
{
    private $data = array();
    /**
     * Search Sb block
     *
     * @return type
     */
    public function build()
    {
        \Drupal::service('page_cache_kill_switch')->trigger();
        
        
        $id = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
        if (!empty($id)) {
            $controller = new \Drupal\acdh_repo_gui\Controller\VersionsController();
            $this->data = $controller->generateView($id);
            $this->checkActualID($id);
        }
        
        if (count($this->data) < 1) {
            $this->data = array();
        }
       
        return [
            '#theme' => 'acdh-repo-gui-detail-versions-block',
            '#result' => $this->data,
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ]
        ];
    }

    /**
     * @return int
     */
    public function getCacheMaxAge()
    {
        return 0;
    }
    
    private function checkActualID(string $id): void
    {
        foreach ($this->data as $k => $v) {
            if ($v->id == $id) {
                $this->data[$k]->actual = "version-highlighted";
            }
        }
    }
}
