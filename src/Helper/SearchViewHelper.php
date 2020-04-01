<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\ArcheHelper;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;

/**
 * Description of SearchViewHelper
 *
 * @author norbertczirjak
 */
class SearchViewHelper extends ArcheHelper {
    
    private $searchViewObjectArray;
    private $siteLang = "en";
    
    public function createView(array $data = array()): array {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        
       
        $this->formatResultToGui($data); 
        
        if(count((array)$this->data) == 0) {
            return array();
        }
        
        foreach ($this->data as $k => $v) {
          
            $this->searchViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        return $this->searchViewObjectArray;
    }
    
    /**
     * We need to format the root results for the gui
     * @return array
     */
    private function formatResultToGui(array $data) {
        if(count((array)$data) > 0) {
            foreach($data as $k => $v) {
                
                
                if($v->property == $this->repo->getSchema()->__get('drupal')->vocabsNamespace.'hasTitle') {
                   $this->data[$v->id]['acdh:hasTitle'] = array(
                        $this->createObj(
                            $v->id, 
                            $v->property, 
                            $v->value, 
                            $v->value,
                            $v->lang
                            )
                        ); 
                }
                
                if($v->property == $this->repo->getSchema()->__get('drupal')->vocabsNamespace.'hasDescription') {
                   $this->data[$v->id]['acdh:hasDescription'] = array(
                        $this->createObj(
                            $v->id, 
                            $v->property, 
                            $v->value, 
                            $v->value,
                            $v->lang
                            )
                        );
                }
            }
        }
    }
    
    /**
     * Create the root object for gui 
     * @param int $id
     * @param string $property
     * @param string $title
     * @param string $value
     * @return object
     */
    private function createObj(int $id, string $property, string $title, string $value, string $lang ): object {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property; //;
        $obj->title = $title;
        $obj->value = $value;
        $obj->lang = $lang;
        return $obj;
    }
    
}
