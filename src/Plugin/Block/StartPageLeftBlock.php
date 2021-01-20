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
        $rvc = new \Drupal\acdh_repo_gui\Controller\RootViewController();
        
        $data = $rvc->generateRootViewData('3', '0', 'datedesc');
        if (!isset($data['data'])) {
            $data['data'] = array();
        }
        //getRepoID
        return [
            '#theme' => 'acdh-repo-gui-main-page-left-block',
            '#result' => $data['data']
        ];
    }
}
