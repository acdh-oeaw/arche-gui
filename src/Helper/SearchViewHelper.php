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
class SearchViewHelper extends ArcheHelper
{
    private $searchViewObjectArray;
    private $metadata;
    private $searchObj;
    private $data = array();
    
    public function createView(array $data = array()): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->formatResultToGui($data);
      
        if (count((array)$this->data) == 0) {
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
    private function formatResultToGui(array $data)
    {
        if (count((array)$data) > 0) {
            foreach ($data as $k => $v) {
                $lang = 'en';
                if (isset($d->language)) {
                    if (!empty($d->language)) {
                        $lang = $d->language;
                    } else {
                        $lang = $this->siteLang;
                    }
                }
                if (isset($v->id)) {
                    $this->data[$k]['acdh:hasIdentifier'][$lang] = array(
                        $this->createObj(
                            $v->id,
                            $this->repo->getSchema()->__get('id'),
                            $v->id,
                            $v->id
                        )
                        );
                    
                    if (isset($v->title)) {
                        $this->data[$k]['acdh:hasTitle'][$lang] = array(
                        $this->createObj(
                            $v->id,
                            $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasTitle",
                            $v->title,
                            $v->title
                        )
                        );
                    }
                    if (isset($v->avdate)) {
                        $this->data[$k]['acdh:hasAvailableDate'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasAvailableDate",
                                $v->avdate,
                                $v->avdate
                            )
                            );
                    }
                    if (isset($v->description)) {
                        $this->data[$k]['acdh:hasDescription'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasDescription",
                                $v->description,
                                $v->description
                            )
                            );
                    }
                    if (isset($v->accesres)) {
                        $this->data[$k]['acdh:hasAccessRestriction'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasAccessRestriction",
                                str_replace("https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/", "", $v->accesres),
                                $v->accesres
                            )
                            );
                    }
                    if (isset($v->titleimage)) {
                        $this->data[$k]['acdh:hasTitleImage'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace."hasTitleImage",
                                $v->titleimage,
                                $v->titleimage
                            )
                            );
                    }
                    //get the acdh type
                    if (isset($v->acdhtype)) {
                        $this->data[$k]['rdf:type'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('namespaces')->rdfs."type",
                                $v->acdhtype,
                                $v->acdhtype
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
    private function createObj(int $id, string $property, string $title, string $value): object
    {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property; //;
        $obj->title = $title;
        $obj->value = $value;
        return $obj;
    }
    
    /**
     * Create object from the search values
     * @param string $metavalue
     * @return object
     */
    public function createMetaObj(string $metavalue): object
    {
        $this->searchObj =  new \stdClass();
        $this->metadata = $metavalue;
        $this->setUpMetadata();
        return $this->searchObj;
    }
    
    /**
     * the search object creation steps
     */
    private function setUpMetadata()
    {
        $this->metadata = urldecode($this->metadata);
        $this->metadata = str_replace(' ', '+', $this->metadata);
        $this->explodeSearchString();
    }
    
    /**
    * Fill the search object with the search metadata
    */
    public function explodeSearchString()
    {
        $filters = array("type", "dates", "words", "mindate", "maxdate", "years", "solrsearch");
        $strArr = explode('&', $this->metadata);
                
        foreach ($filters as $f) {
            foreach ($strArr as $arr) {
                if (strpos($arr, $f) !== false) {
                    $arr = str_replace($f.'=', '', $arr);
                    if (($f == "mindate") || ($f == "maxdate")) {
                        $arr = str_replace('+', '', $arr);
                    }
                    if ($f == 'words') {
                        $arr = explode('+', $arr);
                    }
                    if ($f == 'type') {
                        $arr = explode('+', $arr);
                        if (($key = array_search('or', $arr)) !== false) {
                            unset($arr[$key]);
                        }
                    }
                    if ($f == 'years') {
                        $arr = explode('+', $arr);
                    }
                    $this->searchObj->$f = $arr;
                }
            }
        }
    }
}
