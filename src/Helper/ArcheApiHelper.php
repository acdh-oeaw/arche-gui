<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Model\ArcheApiModel;
use acdhOeaw\arche\lib\Repo;
use acdhOeaw\arche\lib\RepoResource;
use acdhOeaw\arche\lib\RepoDb;
use Drupal\acdh_repo_gui\Helper\MetadataGuiHelper;

/**
 * Description of ArcheApiHelper
 *
 * @author norbertczirjak
 */
class ArcheApiHelper {

    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;

    private $data = array();
    private $apiType = '';
    private $result = array();
    private $properties;
    private $requiredClasses = array();

    public function createView(array $data = array(), string $apiType = '', string $lng = 'en'): array {
        (!empty($lng)) ? $this->siteLang = strtolower($lng) : $this->siteLang = "en";
        if (count((array) $data) == 0 && !empty($apiType)) {
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
            case 'getMembers':
                $this->data = $data;
                $this->formatMembersData();
                break;
            case 'getRPR':
                $this->data = $data;
                $this->formatRPRData();
                break;
            case 'rootTable':
                $mdgh = new \Drupal\acdh_repo_gui\Helper\MetadataGuiHelper();
                $this->result = array($mdgh->getRootTable($data));
                break;
            
        }

        return $this->result;
    }

    /**
     * Create the inverse result for the datatable
     */
    private function formatInverseData() {
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
     * Format the sql result to the getMembers api endpoint
     */
    private function formatMembersData() {
        $this->result = array();
        foreach ($this->data as $obj) {
            $this->result[] = array("<a id='archeHref' href='/browser/oeaw_detail/$obj->id'>$obj->title</a>");
        }
    }

    /**
     * Format the sql result to the Related Publications and Resources api endpoint
     */
    private function formatRPRData() {
        $this->result = array();
        foreach ($this->data as $obj) {
            $this->result[] = array(
                0 => "<a id='archeHref' href='/browser/oeaw_detail/$obj->id'>$obj->title</a>",
                1 => str_replace($this->repo->getSchema()->__get('namespaces')->ontology, '', $obj->relatedtype),
                2 => str_replace($this->repo->getSchema()->__get('namespaces')->ontology, '', $obj->acdhtype)
            );
        }
    }

    /**
     * Create the reponse header
     * @param array $data
     */
    private function setupMetadataType(array $data = array()) {
        $this->creatMetadataObj($data);
        if (count((array) $data['properties']) > 0) {
            $this->data = $data['properties'];
        }

        $this->result['$schema'] = "http://json-schema.org/draft-07/schema#";
        $this->result['id'] = $data['class'];
        $this->result['type'] = "object";
        $this->result['title'] = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $data['class']);
        $this->formatMetadataView();
    }

    /**
     * Format the data for the metadata api request
     */
    private function formatMetadataView() {
        foreach ($this->data as $v) {
            $prop = "";
            if (is_array($v->property)) {
                foreach ($v->property as $key => $value) {
                    if (strpos($value, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false) {
                        $prop = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $value);
                    }
                }
            }else {
                $prop = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
            }
            
            if (isset($v->label) && isset($v->label[$this->siteLang])) {
                $this->result['properties'][$prop]['title'] = $v->label[$this->siteLang];
            }
            if (isset($v->comment) && isset($v->comment[$this->siteLang])) {
              
                $this->result['properties'][$prop]['description'] = $v->comment[$this->siteLang];
                $this->result['properties'][$prop]['attrs']['placeholder'] = $v->comment[$this->siteLang];
            }
            if (isset($v->range)) {
                $range = "";
                $rangeUrl = "";
                foreach ($v->range as $key => $value) {
                    if (strpos($value, 'http://www.w3.org/2001/XMLSchema#') !== false) {
                        $range = str_replace('http://www.w3.org/2001/XMLSchema#', '', $value);
                        $rangeUrl = $value;
                    }
                }
                $this->result['properties'][$prop]['items']['range'] = $rangeUrl;
                if (strpos($range, 'string') !== false) {
                    $this->result['properties'][$prop]['items']['type'] = "string";
                    $this->result['properties'][$prop]['type'] = "string";
                }
                if (strpos($range, 'array') !== false) {
                    $this->result['properties'][$prop]['items']['type'] = "array";
                    $this->result['properties'][$prop]['type'] = "array";
                }
            }

            $this->checkCardinality($prop, $v);

            //order missing!
            if (isset($v->order) && $v->order) {
                $this->result['properties'][$prop]['order'] = (int) $v->order;
            } else {
                $this->result['properties'][$prop]['order'] = 0;
            }
            //recommendedClass missing!
            if (isset($v->recommendedClass) && $v->recommendedClass) {
                $this->result['properties'][$prop]['recommendedClass'] = $v->recommendedClass;
            }
        }
        if (count((array) $this->requiredClasses) > 0) {
            $this->result['required'] = $this->requiredClasses;
        }
    }

    /**
     * Check the property cardinalities
     *
     * @param array $data
     * @return string
     */
    private function checkCardinality(string $prop, object $obj) {
        if (isset($obj->min)) {
            $this->result['properties'][$prop]['minItems'] = (int) $obj->min;
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
            $this->result['properties'][$prop]['maxItems'] = (int) $obj->max;
            if ($obj->max > 1) {
                $this->result['properties'][$prop]['type'] = "array";
            }
        }
        if (isset($obj->min) && $obj->min >= 1) {
            $this->requiredClasses[] = $prop;
        }

        if (isset($obj->cardinality)) {
            $this->result['properties'][$prop]['cardinality'] = $obj->cardinality;
            $this->result['properties'][$prop]['minItems'] = (int) $obj->cardinality;
            $this->result['properties'][$prop]['maxItems'] = (int) $obj->cardinality;
        }
    }

    /**
     * Create properties obj with values from the metadata api request
     * @param array $data
     */
    private function creatMetadataObj(array $data) {
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


}
