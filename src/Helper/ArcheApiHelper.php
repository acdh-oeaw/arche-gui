<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Model\ArcheApiModel;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use Drupal\acdh_repo_gui\Helper\MetadataGuiHelper;

/**
 * Description of ArcheApiHelper
 *
 * @author norbertczirjak
 */
class ArcheApiHelper extends ArcheHelper
{
    private $data = array();
    private $apiType = '';
    private $result = array();
    private $properties;
    private $siteLang;
    private $requiredClasses = array();
    
    public function createView(array $data = array(), string $apiType = '', string $lng ='en'): array
    {
        (!empty($lng)) ? $this->siteLang = strtolower($lng)  : $this->siteLang = "en";
        if (count($data) == 0  && !empty($apiType)) {
            return array();
        }
        
        switch ($apiType) {
            case 'metadata':
                $this->setupMetadataType($data);
                break;
            case 'metadataGui':
                $mdgh = new \Drupal\acdh_repo_gui\Helper\MetadataGuiHelper();
                $this->result = $mdgh->getData($data);
                break;
            case 'inverse':
                $this->data = $data;
                $this->formatInverseData();
                break;
            case 'checkIdentifier':
                $this->data = $data;
                $this->formatCheckIdentifierData();
                break;
            case 'gndPerson':
                $this->data = $data;
                $this->createGNDFile();
                break;
            case 'countCollsBins':
                $this->data = $data;
                $this->formatCollsBinsCount();
                break;
            default:
                $this->data = $data;
                $this->apiType = $apiType;
                $this->formatView();
                break;
        }
        
        return $this->result;
    }
    
    /**
     * Create the inverse result for the datatable
     */
    private function formatInverseData()
    {
        $this->result = array();
        foreach ($this->data as $id => $obj) {
            foreach ($obj as $o) {
                $arr = array(
                    str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', 'acdh:', $o->property),
                    "<a id='archeHref' href='/browser/oeaw_detail/$id'>$o->title</a>"
                );
                $this->result[] = $arr;
            }
        }
    }
    
    /**
     * Create the reponse header
     * @param array $data
     */
    private function setupMetadataType(array $data = array())
    {
        $this->creatMetadataObj($data);
        if (count($data['properties']) > 0) {
            $this->data = $data['properties'];
        }
            
        $this->result['$schema'] = "http://json-schema.org/draft-07/schema#";
        $this->result['id'] = $data['class'];
        $this->result['type'] = "object";
        $this->result['title'] = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $data['class']);
        $this->formatMetadataView();
    }
    
    /**
     * format the collections and binaries count response
     */
    private function formatCollsBinsCount()
    {
        $this->result['$schema'] = "http://json-schema.org/draft-07/schema#";
       
        $collections = "0";
        $files = "0";
        if (isset($this->data[0]->collections) && !empty($this->data[0]->collections)) {
            $collections = $this->data[0]->collections." ".t("collections");
        }
        if (isset($this->data[0]->binaries) && !empty($this->data[0]->binaries)) {
            $files = $this->data[0]->binaries." ".t("files");
        }

        if (empty($files)) {
            $files = "0";
        }
        if (empty($collections)) {
            $collections = "0";
        }
        $this->result['text'] = $collections. " ".t("with")." ".$files;
    }
    
    
    /**
     * Format the data for the metadata api request
     */
    private function formatMetadataView()
    {
        foreach ($this->data as $v) {
            $prop = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
            
            if (isset($v->label)) {
                $this->result['properties'][$prop]['title'] = $v->label[$this->siteLang];
            }
            if (isset($v->comment)) {
                $this->result['properties'][$prop]['description'] = $v->comment[$this->siteLang];
                $this->result['properties'][$prop]['attrs']['placeholder'] = $v->comment[$this->siteLang];
            }
            if (isset($v->range)) {
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
        if (count($this->requiredClasses) > 0) {
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
    private function creatMetadataObj(array $data)
    {
        $this->properties = new \stdClass();
        
        if (isset($data['class'])) {
            $this->properties->class = $data['class'];
        }
        if (isset($data['label'])) {
            $this->properties->label = $data['label'];
        }
        if (isset($data['comment'])) {
            $this->properties->comment = $data['comment'];
        }
    }
    
    /**
     * Format the basic APi views
     */
    private function formatView()
    {
        $this->result = array();
        foreach ($this->data as $k => $val) {
            foreach ($val as $v) {
                $title = $v->value;
                $lang = $v->lang;
                $altTitle = '';
                if ($v->property == 'https://vocabs.acdh.oeaw.ac.at/schema#hasAlternativeTitle') {
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
    
    /**
     * Format the checkIdentifier api call result
     */
    private function formatCheckIdentifierData()
    {
        $this->result = array();
        foreach ($this->data as $val) {
            if ($val->property == 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate') {
                $this->result['hasAvailableDate'] = date('Y-m-d', strtotime($val->value));
            }
            if ($val->property == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle') {
                $this->result['title'] = $val->value;
            }
            if ($val->property == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
                $this->result['rdfType'] = $val->value;
            }
        }
    }
    
    /**
     * create the GNDfile for the GND API
     */
    private function createGNDFile()
    {
        $host = str_replace('http://', 'https://', \Drupal::request()->getSchemeAndHttpHost().'/browser/oeaw_detail/');
        $fileLocation = \Drupal::request()->getSchemeAndHttpHost().'/browser/sites/default/files/beacon.txt';
        
        $this->result = array();
        
        if (count($this->data) > 0) {
            $resTxt = "";
            foreach ($this->data as $val) {
                $resTxt .= $val->gnd."|".$host.$val->repoid." \n";
            }

            if (!empty($resTxt)) {
                $resTxt = "#FORMAT: BEACON \n".$resTxt;
                file_save_data($resTxt, "public://beacon.txt", FILE_EXISTS_REPLACE);
                $this->result = array('fileLocation' => $fileLocation);
            } else {
                $this->result = array();
            }
        }
    }
}
