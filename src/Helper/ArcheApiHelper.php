<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Model\ArcheApiModel;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;

/**
 * Description of ArcheApiHelper
 *
 * @author norbertczirjak
 */
class ArcheApiHelper extends ArcheHelper {
   
    private $data = array();
    private $apiType = '';
    private $result = array();
    private $properties;
    private $siteLang;
    private $requiredClasses = array();
       
   
    public function createView(array $data = array(), string $apiType = '', string $lng ='en'): array {
        (!empty($lng)) ? $this->siteLang = strtolower($lng)  : $this->siteLang = "en";
        if(count($data) == 0  && !empty($apiType)) {
            return array();
        }
        
        if($apiType == 'metadata'){
            $this->creatMetadataObj($data);
            if(count($data['properties']) > 0){
                $this->data = $data['properties'];
            }
            
            $this->result['$schema'] = "http://json-schema.org/draft-07/schema#";
            $this->result['id'] = $data['class'];
            $this->result['type'] = "object";
            $this->result['title'] = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $data['class']);
            $this->formatMetadataView();
           
        }else{
            $this->data = $data;
            $this->apiType = $apiType;
            $this->formatView();
        }
        
        return $this->result;
    }
    
    /**
     * Format the data for the metadata api request
     */
    private function formatMetadataView() {
        
        foreach($this->data as $v){
            $prop = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
            
            if(isset($v->label)){
                $this->result['properties'][$prop]['title'] = $v->label[$this->siteLang];
            }
            if(isset($v->comment)){
                $this->result['properties'][$prop]['description'] = $v->comment[$this->siteLang];
                $this->result['properties'][$prop]['attrs']['placeholder'] = $v->comment[$this->siteLang];
            }
            if(isset($v->range)){
                $this->result['properties'][$prop]['items']['range'] = $v->range;
                if (strpos($v->range, 'string') !== false) {
                    $this->result['properties'][$prop]['items']['type'] = "string";
                    $this->result['properties'][$prop]['type'] = "string";
                }
                if (strpos($v->range, 'array') !== false) {
                    $this->result['properties'][$prop]['items']['type'] = "array";
                    $this->result['properties'][$prop]['type'] = "array";
                }
            }
           
            $this->checkCardinality($prop, $v);
           
            //order missing!
            if (isset($v->order) && $v->order) {
                $this->result['properties'][$prop]['order'] = (int)$v->order;
            } else {
               $this->result['properties'][$prop]['order'] = 0;
            }
            //recommendedClass missing!
            if (isset($v->recommendedClass) && $v->recommendedClass) {
                $this->result['properties'][$prop]['recommendedClass'] = $v->recommendedClass;
            }
            
            
        }
        if(count($this->requiredClasses) > 0) {
           $this->result['required'] = $this->requiredClasses;     
        }
    }
    
    /**
     * Check the property cardinalities
     *
     * @param array $data
     * @return string
     */
    private function checkCardinality(string $prop, object $obj)
    {
        if (isset($obj->min)) {
            $this->result['properties'][$prop]['minItems'] = (int)$obj->min;
            if ($obj->min >= 1) {
                $this->result['properties'][$prop]['type'] = "array";
            }
            if ($obj->min == 1) {
                $this->result['properties'][$prop]['uniqueItems'] = true;
            }
        } else {
            $this->result['properties'][$prop]['minItems'] = 0;
        }

        if (isset($obj->max)) {
            $this->result['properties'][$prop]['maxItems'] = (int)$obj->max;
            if ($obj->max > 1) {
                $this->result['properties'][$prop]['type'] = "array";
            }
        }
        if (isset($obj->min) && $obj->min >= 1) {
            $this->requiredClasses[] = $prop;
        }

        if (isset($obj->cardinality)) {
           $this->result['properties'][$prop]['cardinality'] = $obj->cardinality;
            $this->result['properties'][$prop]['minItems'] = (int)$obj->cardinality;
            $this->result['properties'][$prop]['maxItems'] = (int)$obj->cardinality;
        }
    }
    
    /**
     * Create properties obj with values from the metadata api request
     * @param array $data
     */
    private function creatMetadataObj(array $data) {
        $this->properties = new \stdClass();
        
        if(isset($data['class'])){
            $this->properties->class = $data['class'];
        }
        if(isset($data['label'])){
            $this->properties->label = $data['label'];
        }
        if(isset($data['comment'])){
            $this->properties->comment = $data['comment'];
        }   
    }
    
    /**
     * Format the basic APi views
     */
    private function formatView() {
        $this->result = array();
        foreach ($this->data as $k => $val) {
            foreach($val as $v){
                $title = $v->value;
                $lang = $v->lang;
                $altTitle = '';
                if($v->property == 'https://vocabs.acdh.oeaw.ac.at/schema#hasAlternativeTitle') {
                    $altTitle = $v->value;    
                }
                $this->result[$k]->title[$lang] = $title;
                $this->result[$k]->uri = $this->repo->getBaseUrl().$k;
                $this->result[$k]->identifier = $k;
                $this->result[$k]->altTitle = $altTitle;
            }
        }
        $this->result = array_values($this->result);
    }
}
