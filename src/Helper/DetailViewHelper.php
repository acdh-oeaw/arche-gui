<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoDisserv\RepoResource;

/**
 * Description of DetailViewHelper
 *
 * @author nczirjak
 */
class DetailViewHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;
    
    private $detailViewObjectArray;
    
    /**
     * Build up the necessary data for the detail view
     * @param array $data
     * @return array
     */
    public function createView(array $data = array()): array
    {
        $this->data = array();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->data = $data;
        
        $this->extendActualObj();
        
        if (count((array)$this->data) == 0) {
            return array();
        }
      
        $this->detailViewObjectArray = array();
        $this->detailViewObjectArray[] = new ResourceObject($this->data, $this->repo, $this->siteLang);
        
        return $this->detailViewObjectArray;
    }
    
    /**
     * Format the sql result for the gui
     * @param type $tooltip
     * @return array
     */
    public function formatTooltip($tooltip): array
    {
        $result = array();
        foreach ($tooltip as $t) {
            $result[$t->type] = $t;
        }
        return $result;
    }
}
