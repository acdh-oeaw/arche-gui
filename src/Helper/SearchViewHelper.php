<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Object\ResourceObject;
use Drupal\acdh_repo_gui\Helper\ArcheHelper as ArcheHelper;

/**
 * Description of SearchViewHelper
 *
 * @author norbertczirjak
 */
class SearchViewHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;

    private $searchViewObjectArray;
    private $metadata;
    private $searchObj;
    private $data = array();
    private $objLang = "en";

    public function createView(array $data = array(), int $version = 1): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";

        if ($version == 2) {
            $this->formatResultV2($data);
        } else {
            $this->formatResultToGui($data);
        }
        if (count((array) $this->data) == 0) {
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
        if (count((array) $data) > 0) {
            foreach ($data as $k => $v) {
                $this->setupLang($v);
                if (isset($v->id)) {
                    $this->data[$k]['acdh:hasIdentifier'][$this->objLang] = array(
                        $this->createObj(
                            $v->id,
                            $this->repo->getSchema()->__get('id'),
                            $v->id,
                            $v->id
                        )
                    );

                    if (isset($v->title)) {
                        $this->data[$k]['acdh:hasTitle'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasTitle",
                                $v->title,
                                $v->title
                            )
                        );
                    }
                    if (isset($v->avdate)) {
                        $this->data[$k]['acdh:hasAvailableDate'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasAvailableDate",
                                $v->avdate,
                                $v->avdate
                            )
                        );
                    }
                    if (isset($v->description)) {
                        $this->data[$k]['acdh:hasDescription'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasDescription",
                                $v->description,
                                $v->description
                            )
                        );
                    }
                    if (isset($v->accesres)) {
                        $this->data[$k]['acdh:hasAccessRestriction'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasAccessRestriction",
                                str_replace("https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/", "", $v->accesres),
                                $v->accesres
                            )
                        );
                    }
                    if (isset($v->titleimage)) {
                        $this->data[$k]['acdh:hasTitleImage'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasTitleImage",
                                $v->titleimage,
                                $v->titleimage
                            )
                        );
                    }
                    //get the acdh type
                    if (isset($v->acdhtype)) {
                        $this->data[$k]['rdf:type'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('namespaces')->rdfs . "type",
                                $v->acdhtype,
                                $v->acdhtype
                            )
                        );
                    }

                    if (isset($v->headline)) {
                        $this->data[$k]['headline'][$this->objLang] = array(
                            $this->createObj(
                                $v->id,
                                'search_headline',
                                $v->headline,
                                $v->headline
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
        $this->searchObj = new \stdClass();
        $this->metadata = $metavalue;
        $this->setUpMetadata();
        return $this->searchObj;
    }

    /**
     * the search object creation steps
     */
    private function setUpMetadata(): void
    {
        $this->metadata = urldecode($this->metadata);
        $this->metadata = str_replace(' ', '+', $this->metadata);
        $this->explodeSearchString();
    }

    /**
     * Fill the search object with the search metadata
     */
    public function explodeSearchString(): void
    {
        $filters = array("type", "dates", "words", "mindate", "maxdate", "years", "payload");
        $strArr = explode('&', $this->metadata);

        foreach ($filters as $f) {
            foreach ($strArr as $arr) {
                if (strpos($arr, $f) !== false) {
                    $arr = str_replace($f . '=', '', $arr);
                    
                    if (($f == "mindate") || ($f == "maxdate") || ($f == "words") || ($f == "years")) {
                        $arr = $this->explodeSearchStringValues($arr);
                    }
                    if ($f == "type") {
                        $arr =$this->explodeTypes($arr);
                    }
                    
                    $this->searchObj->$f = $arr;
                }
            }
        }
    }
    
    /**
     * explode the search string  values
     * @param string $data
     * @return string
     */
    private function explodeSearchStringValues(string $data): string {
        return str_replace('+', '', $data);
    }
    
    
    /**
     * Explode the search string types
     * @param string $data
     * @return array
     */
    private function explodeTypes(string $data): array {
        $data = explode('+', $data);
        $res = array();
        if (($key = array_search('or', $data)) !== false) {
            unset($data[$key]);
        }
        
        foreach($data as $k => $v) {
            $res[$k] = ArcheHelper::createFullPropertyFromShortcut($v);
        }
        return $res;
    }

    private function formatResultV2(array $data)
    {
        if (count((array) $data) > 0) {
            foreach ($data as $k => $v) {
                $this->setupLang($v);

                if (isset($v->acdhid)) {
                    $this->data[$k]['acdh:hasIdentifier'][$this->objLang] = array(
                        $this->createObj(
                            $v->acdhid,
                            $this->repo->getSchema()->__get('id'),
                            $v->acdhid,
                            $v->acdhid
                        )
                    );
                    //add all ids to the obj
                    if (isset($v->ids)) {
                        $ids = explode(",", $v->ids);
                        foreach ($ids as $id) {
                            $this->data[$k]['acdh:hasIdentifier'][$this->objLang] = array(
                                $this->createObj(
                                    $v->acdhid,
                                    $this->repo->getSchema()->__get('id'),
                                    $id,
                                    $id
                                )
                            );
                        }
                    }
                    if (isset($v->title)) {
                        $this->data[$k]['acdh:hasTitle'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasTitle",
                                $v->title,
                                $v->title
                            )
                        );
                    }
                    if (isset($v->avdate)) {
                        $this->data[$k]['acdh:hasAvailableDate'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasAvailableDate",
                                $v->avdate,
                                $v->avdate
                            )
                        );
                    }
                    if (isset($v->description)) {
                        $this->data[$k]['acdh:hasDescription'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasDescription",
                                $v->description,
                                $v->description
                            )
                        );
                    }
                    if (isset($v->accessres)) {
                        $this->data[$k]['acdh:hasAccessRestriction'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasAccessRestriction",
                                str_replace("https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/", "", $v->accessres),
                                $v->accessres
                            )
                        );
                    }
                    if (isset($v->titleimage)) {
                        $this->data[$k]['acdh:hasTitleImage'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('drupal')->vocabsNamespace . "hasTitleImage",
                                $v->titleimage,
                                $v->titleimage
                            )
                        );
                    }
                    //get the acdh type
                    if (isset($v->acdhtype)) {
                        $this->data[$k]['rdf:type'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                $this->repo->getSchema()->__get('namespaces')->rdfs . "type",
                                $v->acdhtype,
                                $v->acdhtype
                            )
                        );
                    }

                    if (isset($v->headline_title)) {
                        $this->data[$k]['headline_title'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                'headline_title',
                                $v->headline_title,
                                $v->headline_title
                            )
                        );
                    }
                    if (isset($v->headline_desc)) {
                        $this->data[$k]['headline_desc'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                'headline_desc',
                                $v->headline_desc,
                                $v->headline_desc
                            )
                        );
                    }
                    if (isset($v->headline_binary)) {
                        $this->data[$k]['headline_binary'][$this->objLang] = array(
                            $this->createObj(
                                $v->acdhid,
                                'headline_binary',
                                $v->headline_binary,
                                $v->headline_binary
                            )
                        );
                    }
                }
            }
        }
    }

    /**
     * get the language for the result object
     * @param string $v
     * @return string
     */
    private function setupLang(object &$v)
    {
        if (isset($v->language) && !empty($v->language)) {
            $this->objLang = $v->language;
        } elseif (isset($this->siteLang) && !empty($this->siteLang)) {
            $this->objLang = $this->siteLang;
        }
        $this->objLang = 'en';
    }
}
