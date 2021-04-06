<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Description of ArcheBaseController
 *
 * @author nczirjak
 */
class ArcheBaseController extends ControllerBase {

    protected $config;
    protected $repo;
    protected $repodb;
    protected $siteLang;
    protected $helper;
    protected $model;

    public function __construct() {
        $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml';
        $this->repo = \acdhOeaw\acdhRepoLib\Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
        $this->repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory($this->config);
    }

}
