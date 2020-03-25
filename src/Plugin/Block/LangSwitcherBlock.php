<?php
/**
 * @file
 * Contains \Drupal\acdh_repo_gui\Plugin\Block\LangSwitcherBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'LangSwitcherBlock' block.
 *
 * @Block(
 *   id = "lang_switcher_block",
 *   admin_label = @Translation("OEAW Language Switcher"),
 *   category = @Translation("Custom oeaw language switcher")
 * )
 */
class LangSwitcherBlock extends BlockBase
{

    /**
     * Class block
     *
     * @return type
     */
    public function build()
    {
        if (isset($_SESSION['language'])) {
            $lang = strtolower($_SESSION['language']);
        } else {
            $lang = "en";
        }
        
        $return = array(
            '#theme' => 'helper-lng-switcher',
            '#language' => $lang,
            '#attached' => [
                'library' => [
                'acdh_repo_gui/repo-styles', //include our custom library for this response
                ]
            ]
        );
        return $return;
    }
}
