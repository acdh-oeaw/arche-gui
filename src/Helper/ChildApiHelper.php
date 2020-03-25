<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;
use Drupal\acdh_repo_gui\Helper\ArcheHelper;

/**
 * Description of ApiViewHelper
 *
 * @author nczirjak
 */
class ChildApiHelper extends ArcheHelper {
    
    private $rootViewObjectArray;
    private $lng = "en";
        
    /**
     * Child view create
     * 
     * @param array $data
     * @return array
     */    
    public function createView(array $data = array()): array {
        $this->data = $data;
        $this->extendActualObj();  
        
        if(count((array)$this->data) == 0) {
            return array();
        }
        
        foreach ($this->data as $k => $v) {
            $this->rootViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        return $this->rootViewObjectArray;
    }
    
   
    /**
     * We need to overwrite the basic function, because the child view has a different sql output
     * 
     * @param bool $root
     */
    protected function extendActualObj(bool $root = false) {
        $result = array();
        
        foreach($this->data as $k => $v) {
            foreach($v as $obj) {
                if(isset($obj->property)) {
                    $obj->shortcut = $this->createShortcut($obj->property);
                    if(isset($obj->value)) {
                        $obj->title = $obj->value;
                    }
                    $obj->id = $obj->childid;
                    if(!isset($result[$k]['acdh:hasIdentifier'])){
                        $o = new \stdClass();
                        $o->id = '';
                        $o->id = $obj->id;
                        $result[$k]['acdh:hasIdentifier'][0] = $o;
                    }
                    //we need to change the postgre datetime format to php date to we can modify it with the twig template
                    if(isset($result[$k]['acdh:hasAvailableDate'])){
                        $time = strtotime($result[$k]['acdh:hasAvailableDate'][0]->value);
                        $newformat = date('Y-m-d h:m:s',$time);
                        $result[$k]['acdh:hasAvailableDate'][0]->title = $newformat;
                    }
                    $result[$k][$obj->shortcut][] = $obj;
                }
            }
        }
        $this->data = $result;
    }
}
