<?php

namespace Drupal\acdh_repo_gui\Traits;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use acdhOeaw\arche\lib\Repo;
use Drupal\acdh_repo_gui\Helper\ArcheHelper as Helper;

/**
 * Description of ArcheHelper
 *
 * @author nczirjak
 */
trait ArcheUtilTrait
{
    protected $generalFunctions;
    protected $config;
    protected $repo;
    protected $siteLang;
   
    public function __construct($cfg = null)
    {
        ($cfg && is_string($cfg)) ?  $this->config = $cfg : $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml';
        $this->generalFunctions = new GeneralFunctions($this->config);
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
   
    /**
     * Create gui inside uri from the identifier
     *
     * @param string $data
     * @return string
     */
    protected function makeInsideUri(string $data): string
    {
        if (!empty($data)) {
            return $this->generalFunctions->detailViewUrlDecodeEncode($data, 1);
        }
        return "";
    }
    
    /**
     * Set the language tag for the object
     * @param object $obj
     * @return string
     */
    private function setLanguage(object $obj): string
    {
        $lang = 'en';
        if (isset($obj->language) && (!empty($obj->language))) {
            $lang = $obj->language;
        } else {
            $lang = $this->siteLang;
        }
        
        return $lang;
    }
    
    private function setUpRepoIdForExternalType(object &$d): void
    {
        if (isset($d->type) && !empty($d->type) && $d->type == "REL") {
            $d->repoid = "";
            $d->repoid = $d->value;
            $d->value = $d->relvalue;
        }
    }
    
    private function setTitle(object &$d): void
    {
        if ((isset($d->title) && empty($d->title)) ||
            !isset($d->title)) {
            $d->title = "";
            $d->title = $d->value;
        }
        
        if (isset($d->relvalue) && !empty($d->relvalue)) {
            $d->title = $d->relvalue;
        }
    }
    
    private function setAcdhId(object &$d): void
    {
        if (isset($d->acdhid) && !empty($d->acdhid)) {
            $d->insideUri = "";
            $d->insideUri = $this->makeInsideUri($d->acdhid);
        }
    }
    
    private function setUri(object &$d): void
    {
        if (isset($d->vocabsid) && !empty($d->vocabsid)) {
            $d->uri = $d->vocabsid;
            unset($d->insideUri);
        }
    }
    /**
     * Extend the actual object with the shortcuts
     *
     */
    protected function extendActualObj(bool $root = false)
    {
        $result = array();
      
        foreach ($this->data as $d) {
            
            //add the language to every resource
            $lang = $this->setLanguage($d);
            
            // if we have an external type () then the repoid will be the value
            //because we will use their own id for the  linking
            //and the value will be the relvalue because of the relational data
            $this->setUpRepoIdForExternalType($d);
           
            if (is_null($d->property) === false) {
                //create the shortcut
                $this->setTitle($d);
                $this->setAcdhId($d);
                $d->shortcut = Helper::createShortcut($d->property);
                
                //if we have vocabsid then it will be the uri, to forward the users to the vocabs website
                $this->setUri($d);
                
                //check and remove the duplicated values from the results
                if (!isset($result[$d->shortcut][$lang])) {
                    $d->language = $this->siteLang;
                    $result[$d->shortcut][$this->siteLang][] = $d;
                } elseif (isset($result[$d->shortcut][$lang]) && (count($result[$d->shortcut][$lang]) > 0)) {
                    //if we already have a shortcut in the results then we need to check if it id a relation
                    //or any other type
                    
                    //this is a relation so we need to check the duplicates
                    if (isset($d->repoid)) {
                        //we ahve shorcut and repoid and already results in the result array
                        $searchedValue = $d->repoid;
                        $res = array();
                        //with the array filter we check the objects and the repoid is the same like
                        //what we already have in the array, then we will skip the results array extension
                        $res = array_filter(
                            $result[$d->shortcut][$lang],
                            function ($e) use (&$searchedValue, &$d) {
                                if ($e->repoid != $searchedValue) {
                                    return true;
                                }
                            }
                        );

                        //if we have new value for the same shortcut then add it to the array
                        if (isset($res[0]->repoid)) {
                            $result[$d->shortcut][$lang][] = $d;
                        }
                    } else {
                        //simple string or date, then we simply extending the actual results array
                        $result[$d->shortcut][$lang][] = $d;
                    }
                }
            } elseif (isset($d->type) && !empty($d->type) && $d->type == "ID") {
                //setup the acdh uuid variable
                $d->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
                
                $this->setIDTypeAcdhId($d);
                $this->setIDTypeRepoId($d);
                
                //the uri for the identifier urls
                $this->setIDTypeUri($d);
                //add the identifier into the final data
                $result['acdh:hasIdentifier'][$lang][] = $d;
            }
        }
        
        if ($root == true) {
            ksort($result);
        }
        
        $this->data = $result;
    }
    
    private function setIDTypeAcdhId(object &$d): void
    {
        if (strpos($d->value, '/id.acdh.oeaw.ac.at/uuid/') !== false) {
            $d->acdhid = $d->value;
        }
    }
    
    private function setIDTypeRepoId(object &$d): void
    {
        if (strpos($d->value, '//repo.') !== false) {
            $d->repoid = $d->id;
        }
    }
    
    private function setIDTypeUri(object &$d): void
    {
        if (strpos($d->value, 'http') !== false) {
            $d->uri = $d->value;
        }
    }
    
    public function createResourceObject(array $data): array
    {
        $arr = array();
       
        foreach ($data as $k => $v) {
            $arr[] = new \Drupal\acdh_repo_gui\Object\ResourceObject($v, $this->repo);
        }
        return $arr;
    }
    
    /**
    * Get the count from the rest api result
    * @param array $obj
    * @return int
    */
    public function getCount(array &$obj): int
    {
        $values = array();
        $values = array_map(function ($obj) {
            if (isset($obj['search://count']) && $obj['search://count'] !== null) {
                return $obj['search://count'];
            }
        }, $obj);

        $values = array_filter($values);
        $keys = array_keys($values);
        $key = $keys[0];
       
        if (isset($values[(int)$key][0]['value'])) {
            unset($obj[(int)$key]);
            return $values[(int)$key][0]['value'];
        }
        return 0;
    }
    
    /**
     * Process the easyrdf graph data and create ResourceObject arrays
     * @param array $obj
     * @return array
     */
    public function processGraph(array $obj): array
    {
        $result = array();
        for ($i = 0; $i <= count($obj); $i++) {
            if (isset($obj[$i][$this->repo->getSchema()->id])) {
                $id = $this->getResourceRepoIdentifier($obj[$i][$this->repo->getSchema()->id]);
                if (!empty($id)) {
                    foreach ($obj[$i] as $ok => $ov) {
                        $sc = Helper::createShortcut($ok);
                        if (!empty($sc)) {
                            $result[$id][$sc] = $this->getObjectValues($ov);
                        }
                    }
                }
            }
        }

        return $this->createResourceObject($result);
    }

    private function getObjectValues(array $data): array
    {
        $res = array();

        foreach ($data as $k => $v) {
            if (isset($v['type']) && strtolower($v['type']) == 'literal') {
                $lang = (isset($v['lang'])) ? $v['lang'] : "en";
                $res[$lang][] = $this->getLiteralValues($v);
            } else {
                $res[$this->siteLang][] = $this->getUriValues($v);
            }
        }
        return $res;
    }

    private function getLiteralValues(array $v): object
    {
        if (isset($v['value']) && !empty($v['value'])) {
            return
                    (object) array(
                        'value' => $v['value'],
                        'title' => $v['value']
            );
        }
        return (object) array();
    }

    private function getUriValues(array $v): object
    {
        if (isset($v['value']) && !empty($v['value'])) {
            return
                    (object) array(
                        'value' => $v['value'],
                        'title' => $v['value']
            );
        }
        return (object) array();
    }

    private function getResourceRepoIdentifier($id): string
    {
        foreach ($id as $i) {
            $rid = $this->generalFunctions->getRepoIdFromApiUrl($i['value']);
            if (!empty($rid)) {
                return $rid;
            }
        }
        return "";
    }
}
