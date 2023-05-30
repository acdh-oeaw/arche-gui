<?php
/**
 * @file
 * Contains \Drupal\oeaw\Plugin\Block\SmartSearchBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SmartSearchBlock' block.
 *
 * @Block(
 *   id = "smartsearchblock",
 *   admin_label = @Translation("SmartSearchBlock"),
 *   category = @Translation("Provides search bar and latest additions")
 * )
 */
class SmartSearchBlock extends BlockBase
{
   
    /**
     * Left block build function
     * @return type
     */
    public function build()
    {
        $data = array();
        
        return [
            '#theme' => 'arche-smart-search-view',
            '#data' => null,
            '#properties' => null,
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/smart-search-style',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }
}
