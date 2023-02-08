<?php

namespace Drupal\acdh_repo_gui\Plugin\Block;

/**
 * Provides a 'VCR' block.
 *
 * @Block(
 *   id = "arche_vcr_block",
 *   admin_label = @Translation("VCR Form"),
 *   category = @Translation("ARCHE Clarin VRC Submit Block")
 * )
 */
class VcrSubmitBlock extends \Drupal\Core\Block\BlockBase
{
    public function build()
    {
        \Drupal::service('page_cache_kill_switch')->trigger();
        $ge = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions();
        $vcrUrl = $ge->initClarinVcrUrl();
        return [
            '#theme' => 'acdh-repo-gui-vcr-block',
            '#vcrUrl' => $vcrUrl,
            '#cache' => ['max-age' => 0],
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-search',
                ]
            ]
        ];
    }
}
