<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;

/**
 * Description of RootViewHelper
 *
 * @author nczirjak
 */
class RootViewHelper extends \Drupal\acdh_repo_gui\Helper\ArcheHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;
    
    private $propData = array();
    private $rootViewObjectArray;
    private $viewProperties = [];
    private $result = [];
    
    /**
     * The properties what we need for this view
     */
    private function setProperties()
    {
        $this->properties = array(
            'acdhid' => array('shortcut' => 'acdh:hasIdentifier', 'property' => $this->repo->getSchema()->__get('id')),
            'description' => array('shortcut' => 'acdh:hasDescription', 'property' => $this->repo->getSchema()->__get('namespaces')->ontology."hasDescription"),
            'avdate' => array('shortcut' => 'acdh:hasAvailableDate', 'property' => $this->repo->getSchema()->creationDate),
            'title' => array('shortcut' => 'acdh:hasTitle', 'property' => $this->repo->getSchema()->label)
        );
    }
    
    /**
     * Fetch the property values and create the response object
     * @param string $k
     * @param object $v
     * @param string $lang
     * @return void
     */
    private function fetchProperties(string $k, object $v, string $lang): void
    {
        foreach ($this->properties as $pk => $pv) {
            if (isset($v->$pk)) {
                $title = $v->$pk;
                
                if ($v->$pk == 'accesres') {
                    $title = str_replace("https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/", "", $v->$pk);
                }
                
                $this->data[$k][$pv['shortcut']][$lang] = array(
                    $this->createObj(
                        $v->id,
                        $pv['property'],
                        $title,
                        $v->$pk
                    )
                );
            }
        }
    }
    
   
    public function createView(array $data = array()): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->setProperties();
        $this->formatResultToGui($data);
       
        if (count((array)$this->data) == 0) {
            return array();
        }
       
        foreach ($this->data as $k => $v) {
            $this->rootViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        
        return $this->rootViewObjectArray;
    }
    
    private function setLanguage(object &$v): string
    {
        if (isset($v->language)) {
            if (!empty($v->language)) {
                return $v->language;
            }
        }
        return $this->siteLang;
    }
    
    private function addTopCollectionProperty(string $lang, int $k, object &$v): void
    {
        $this->data[$k]['rdf:type'][$lang] = array(
            $this->createObj(
                $v->id,
                $this->repo->getSchema()->namespaces->rdfs.'type',
                $this->repo->getSchema()->__get('namespaces')->ontology. "TopCollection",
                $this->repo->getSchema()->__get('namespaces')->ontology. "TopCollection"
            )
        );
    }
    
    private function formatResultToGui(array $data)
    {
        if (count((array) $data) > 0) {
            foreach ($data as $k => $v) {
                $lang = $this->setLanguage($v);
                if (isset($v->id)) {
                    $this->fetchProperties($k, $v, $lang);
                    
                    $this->addTopCollectionProperty($lang, $k, $v);
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
    private function createObj(int $id, string $property, string $title, string $value): object
    {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property;
        $obj->title = $title;
        $obj->value = $value;
        return $obj;
    }
    
    public function createViewApi(array $data): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        
        $this->formatValuesToObject($data);
        
        foreach ($this->result as  $v) {
            $this->rootViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        return $this->rootViewObjectArray;
    }
    
    /**
     * Format the Db result to  ResourceObject values
     * @param array $data
     */
    protected function formatValuesToObject(array $data)
    {
        $this->setViewProperties();
        $this->propData = $data;
        $this->fetchProperties();
    }
 
    
    /**
    * Set the properties what we need for this view, we will fetch them from the results array
    */
    private function setViewProperties()
    {
        $this->viewProperties = [
            
            'acdh:hasIdentifier' => $this->repo->getSchema()->__get('id'),
            'acdh:hasTitle' => $this->repo->getSchema()->label,
            'acdh:hasAvailableDate' =>  $this->repo->getSchema()->creationDate,
            'acdh:hasDescription' => $this->repo->getSchema()->__get('namespaces')->ontology."hasDescription",
            'rdf:type' => 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
        ];
    }
}
