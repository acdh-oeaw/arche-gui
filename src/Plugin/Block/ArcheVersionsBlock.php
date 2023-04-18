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
        
        return [
            '#theme' => 'acdh-repo-gui-detail-versions-block-empty',
            '#result' => "",
            '#cache' => ['max-age' => 0],
            
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
