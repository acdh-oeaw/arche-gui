<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;
use Drupal\acdh_repo_gui\Helper\ArcheHelper;

/**
 * Description of RootViewHelper
 *
 * @author nczirjak
 */
class RootViewHelper extends ArcheHelper {
    
    private $rootViewObjectArray;
    private $lng = "en";
    
    
    public function createView(array $data = array()): array {
        
        $this->formatResultToGui($data);  
        if(count((array)$this->data) == 0) {
            return array();
        }
        
        foreach ($this->data as $k => $v) {
            $this->rootViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        
        return $this->rootViewObjectArray;
    }
    
    /**
     * We need to format the root results for the gui
     * @return array
     */
    private function formatResultToGui(array $data) {
        if(count((array)$data) > 0) {
            foreach($data as $k => $v) {
                if(isset($v->id)) {
                    $this->data[$k]['acdh:hasIdentifier'] = array(
                        $this->createObj(
                            $v->id, 
                            $this->repo->getSchema()->__get('id'), 
                            $v->id, 
                            $v->id
                            )
                        );
                    if(isset($v->title)) {
                        $this->data[$k]['acdh:hasTitle'] = array(
                            $this->createObj(
                                $v->id, 
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasTitle", 
                                $v->title, 
                                $v->title
                                )
                            );
                    }
                    if(isset($v->avdate)) {
                        $this->data[$k]['acdh:hasAvailableDate'] = array(
                            $this->createObj(
                                $v->id, 
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasAvailableDate", 
                                $v->avdate, 
                                $v->avdate
                                )
                            );
                    }
                    if(isset($v->description)) {
                        $this->data[$k]['acdh:hasDescription'] = array(
                            $this->createObj(
                                $v->id, 
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasDescription", 
                                $v->description, 
                                $v->description
                                )
                            );
                    }
                    if(isset($v->accresres)) {
                        $this->data[$k]['acdh:hasAccessRestriction'] = array(
                            $this->createObj(
                                $v->id, 
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasAccessRestriction", 
                                $v->accresres, 
                                $v->accresres
                                )
                            );
                    }
                    if(isset($v->titleimage)) {
                        $this->data[$k]['acdh:hasTitleImage'] = array(
                            $this->createObj(
                                $v->id, 
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasTitleImage", 
                                $v->titleimage, 
                                $v->titleimage
                                )
                            );
                    }    
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
    private function createObj(int $id, string $property, string $title, string $value ): object {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property; //;
        $obj->title = $title;
        $obj->value = $value;
        return $obj;
    }
}
