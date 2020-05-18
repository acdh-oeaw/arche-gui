<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoDisserv\RepoResource;

use Drupal\acdh_repo_gui\Helper\ArcheHelper;

/**
 * Description of DetailViewHelper
 *
 * @author nczirjak
 */
class DetailViewHelper extends ArcheHelper
{
    private $detailViewObjectArray;
    private $siteLang;
    
    /**
     * Build up the necessary data for the detail view
     * @param array $data
     * @param array $vocabs
     * @return array
     */
    public function createView(array $data = array(), array $vocabs = array()): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->data = $data;
        
        $this->extendActualObj();
        $this->mergeAccessRes();
        
        //use the drupal vocabs cache if we dont have data in ontology cache
        if (count($vocabs) == 0) {
            $this->getVocabsForDetailViewTable();
        }
        
        if (count((array)$this->data) == 0) {
            return array();
        }
        
        $this->detailViewObjectArray[] = new ResourceObject($this->data, $this->repo, $this->siteLang);
        
        return $this->detailViewObjectArray;
    }
    
    //remove the duplicate value from the accessres
    private function mergeAccessRes()
    {
        foreach ($this->data as $k => $v) {
            if ($k == 'acdh:hasAccessRestriction') {
                foreach ($v as $lk => $lang) {
                    foreach ($lang as $key => $val) {
                        if (strpos($val->relvalue, 'https://vocabs.') === false) {
                            unset($this->data[$k][$lk][$key]);
                        }
                    }
                }
            }
        }
    }
  
   
   
    /**
     *  Update the actual resource values with the right vocabs
     */
    private function getVocabsForDetailViewTable()
    {
        $vf = new \Drupal\acdh_repo_gui\Helper\CacheVocabsHelper();
        $vocabs = array();
        $vocabs = $vf->getVocabsTitle($this->siteLang);
        $lang = $this->siteLang;
        if (count((array)$vocabs[$this->siteLang]) > 0) {
            foreach ($vocabs[$this->siteLang] as $k => $v) {
                //if we have the property inside our table results
                if (isset($this->data[$k]) && count($this->data[$k]) > 0) {
                    foreach ($this->data[$k][$this->siteLang] as $tk => $tv) {
                        foreach ($vocabs[$this->siteLang][$k] as $vocab) {
                            if (isset($vocab->uri) && !empty($vocab->uri) && isset($tv->relvalue)) {
                                if ($vocab->uri == $tv->relvalue) {
                                    $this->data[$k][$this->siteLang][$tk]->uri = $vocab->uri;
                                    $this->data[$k][$this->siteLang][$tk]->title = $vocab->label;
                                }
                            }
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Format the sql result for the gui
     * @param type $tooltip
     * @return array
     */
    public function formatTooltip($tooltip): array
    {
        $result = array();
        foreach ($tooltip as $t) {
            $result[$t->type] = $t;
        }
        return $result;
    }
}
