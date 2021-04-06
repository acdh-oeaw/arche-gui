<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\acdh_repo_gui\Controller;

/**
 * Description of VersionsController
 *
 * @author nczirjak
 */
class VersionsController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    public function __construct()
    {
        parent::__construct();
        $this->model = new \Drupal\acdh_repo_gui\Model\BlocksModel();
    }
    
    public function generateView(string $identifier): array
    {
        return $this->model->getViewData('versions', array('identifier' => $identifier, 'lang' => $this->siteLang));
    }
}
