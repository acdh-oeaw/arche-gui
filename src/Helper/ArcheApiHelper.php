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
    
   
    public function createView(array $data = array(), string $apiType = ''): array {
        
        if(count($data) == 0  && !empty($apiType)) {
            return array();
        }
        
        $this->data = $data;
        $this->apiType = $apiType;
        $this->formatView();
        return $this->result;
    }
    
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
