<?php
/**
 * @file
 * Contains \Drupal\oeaw\Plugin\Block\StartPageLeftBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Controller\RootViewController;

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
    private $RVC;
    private $config;
    /**
     * Left block build function
     * @return type
     */
    public function build()
    {
        $result = array();
        $this->RVC = new RootViewController($this->config);
        $this->config = Repo::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml');
        
        $data = $this->RVC->generateRootView('3', '0', 'datedesc');
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
