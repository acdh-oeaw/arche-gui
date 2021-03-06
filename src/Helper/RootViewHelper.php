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
class RootViewHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;
    
    private $rootViewObjectArray;
   
    public function createView(array $data = array()): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->formatResultToGui($data);
       
        if (count((array)$this->data) == 0) {
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
    private function formatResultToGui(array $data)
    {
        if (count((array)$data) > 0) {
            foreach ($data as $k => $v) {
                //set up a basic language
                $lang = 'en';
                if (isset($v->language) && !empty($v->language)) {
                    $lang = $v->language;
                } else {
                    $lang = $this->siteLang;
                }
                
                //create the acdh:hasIdentifier value from the id
                if (isset($v->id)) {
                    $this->data[$k]['acdh:hasIdentifier'][$lang] = array(
                        $this->createObj(
                            $v->id,
                            $this->repo->getSchema()->__get('id'),
                            $v->id,
                            $v->id
                        )
                    );
                    
                    // Add the acdhid to the acdh:hasIdentifier array too
                    if (isset($v->acdhid)) {
                        $this->data[$k]['acdh:hasIdentifier'][$lang] = array(
                            $this->createObj(
                                $v->id,
                                $this->repo->getSchema()->__get('id'),
                                $v->acdhid,
                                $v->acdhid
                            )
                        );
                    }
                    
                    // create the acdh:hasTitle
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
                    
                    // create the acdh:hasAvailableDate
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
                    
                    // create the acdh:hasDescription
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
                    
                    // create the acdh:hasAccessRestriction
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
                    
                    // create the acdh:hasTitleImage
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
                    
                    // create the rdf:type because all of them will be a collection / or later we can extend the sql result with this
                    $this->data[$k]['rdf:type'][$lang] = array(
                        $this->createObj(
                            $v->id,
                            'http://www.w3.org/1999/02/22-rdf-syntax-ns#type',
                            $this->repo->getSchema()->__get('drupal')->vocabsNamespace."TopCollection",
                            $this->repo->getSchema()->__get('drupal')->vocabsNamespace."TopCollection"
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
    private function createObj(int $id, string $property, string $title, string $value): object
    {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property;
        $obj->title = $title;
        $obj->value = $value;
        return $obj;
    }
}
