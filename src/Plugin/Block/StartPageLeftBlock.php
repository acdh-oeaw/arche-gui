<?php
/**
 * @file
 * Contains \Drupal\oeaw\Plugin\Block\StartPageLeftBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'StartPageLeftBlock' block.
 *
 * @Block(
 *   id = "startpageleftblock",
 *   admin_label = @Translation("Start Page Left Block"),
 *   category = @Translation("Provides search bar and latest additions")
 * )
 */
class StartPageLeftBlock extends BlockBase
{
   
    /**
     * Left block build function
     * @return type
     */
    public function build()
    {
        $data = array();
        
        return [
            '#theme' => 'acdh-repo-gui-main-page-left-block-empty',
            //'#result' => $data['data']
            '#result' => []
        ];
    }
}
