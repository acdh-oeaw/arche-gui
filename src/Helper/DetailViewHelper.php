<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Object\ResourceObject;

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
}
