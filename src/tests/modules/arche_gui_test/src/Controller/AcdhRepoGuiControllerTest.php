<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\examples\Utility\DescriptionTemplateTrait;

/**
 * Simple controller class used to test the DescriptionTemplateTrait.
 */
class AcdhRepoGuiControllerTest extends ControllerBase {
  use DescriptionTemplateTrait;

  /**
   * {@inheritdoc}
   */
  protected function getModuleName() {
    return 'acdh_repo_gui';
  }

  /**
   * {@inheritdoc}
   *
   * We override this so we can see some substitutions.
   */
  protected function getDescriptionVariables() {
    $variables = [
      'module' => $this->getModuleName(),
      'slogan' => $this->t('We aim to please'),
    ];
    return $variables;
  }

}
