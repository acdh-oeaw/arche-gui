<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoDisserv\RepoResource;

use Drupal\acdh_repo_gui\Helper\ArcheHelper;
/**
 * Description of DetailViewHelper
 *
 * @author nczirjak
 */
class DetailViewHelper extends ArcheHelper {
    
    private $detailViewObjectArray;
    private $lng = "en";
    
    /**
     * 
     * Build up the necessary data for the detail view 
     * 
     * @param array $data
     * @return array
     */
    public function createView(array $data = array(), string $dissemination = ''): array {
        $this->data = $data;
        $this->extendActualObj();
        
        if(count((array)$this->data) == 0) {
            return array();
        }
        $this->detailViewObjectArray[] = new ResourceObject($this->data, $this->repo);
        
        return $this->detailViewObjectArray;
    }
  
   
    
}
