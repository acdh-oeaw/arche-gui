<?php
/**
 * @file
 * Contains \Drupal\acdh_repo_gui\Plugin\Block\SearchSBBlock.
 */

namespace Drupal\acdh_repo_gui\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'SearchSD' block.
 *
 * @Block(
 *   id = "reposearch_block",
 *   admin_label = @Translation("Search"),
 *   category = @Translation("Custom complex search oeaw repo")
 * )
 */
class RepoSearchBlock extends BlockBase
{

    /**
     * Search Sb block
     *
     * @return type
     */
    public function build()
    {
        $form = \Drupal::formBuilder()->getForm('Drupal\acdh_repo_gui\Form\ComplexSearchForm');
        return $form;
    }
}
