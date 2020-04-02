<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoLib\SearchConfig;
use acdhOeaw\acdhRepoLib\RepoResourceInterface;
use acdhOeaw\acdhRepoLib\SearchTerm;
/**
 * Description of SearchViewModel
 *
 * @author nczirjak
 */
class SearchViewModel extends ArcheModel {
    
    private $repodb;
    private $repolibDB;
    private $sqlResult;
    private $siteLang;
    private $searchCfg;
    private $metaObj;
    private $searchData = array();
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->searchCfg = new \acdhOeaw\acdhRepoLib\SearchConfig();
        $this->repolibDB = \acdhOeaw\acdhRepoLib\RepoDb::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml', 'guest');
        $this->metaObj = new \stdClass();
    }
    
     /**
     * get the search view data
     * 
     * @return array
     */
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null): array {
        
        //helper function to create object from the metavalue string
        $this->metaObj = $metavalue;
       
        if(isset($this->metaObj->words) && isset($this->metaObj->type)) {
            $this->getWordsFromDB($this->metaObj);
        } else if(isset($this->metaObj->type)) {
            $this->getTypesFromDB();
        } else if(isset($this->metaObj->words)) {
            $this->getWordsFromDB();
        }
        if(isset($metavalue->years)) {
            echo 'we have years';
        }
        //$this->searchCfg->ftsQuery             = "Wollmilchsau";
        //$repodb->setQueryLog($log);
        
        return $this->sqlResult;
    }
    
    /**
     * Get just the types from the db
     */
    private function getTypesFromDB() {
        $this->searchCfg->metadataMode = RepoResourceInterface::META_RESOURCE; 
        $result = array();
        foreach ($this->metaObj->type as $t) {
            $result = $this->repolibDB->getPdoStatementBySearchTerms(
                [new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=')], 
                $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
        }
        if(count($result) > 0 ){
            $this->mergeResults($result);
        }
        if(count($this->searchData) > 0) {
           $this->filterTitleDescription(); 
        }
    }
    
    /**
     * get the resources with the words from the search url
     */
    private function getWordsFromDB(object $types = null) {
        
        $this->searchCfg->metadataMode = RepoResourceInterface::META_RESOURCE; 
        $result = array();
        $isType = false;
        $typeStr = '';
        //new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#Collection', '=')
        if( isset($types) && count($types->type) > 0) {
            foreach($this->metaObj->type as $t) {
                foreach ($this->metaObj->words as $w) {
                    $result['title'] = $this->repolibDB->getPdoStatementBySearchTerms(
                        [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', $w, '@@'), new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=' )], 
                        $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
                    $result['description'] = $this->repolibDB->getPdoStatementBySearchTerms(
                        [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', $w, '@@'), new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=' )], 
                        $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
                }   
            }
        }else {
        
            foreach ($this->metaObj->words as $w) {
                $result['title'] = $this->repolibDB->getPdoStatementBySearchTerms(
                    [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', $w, '@@')], 
                    $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
                $result['description'] = $this->repolibDB->getPdoStatementBySearchTerms(
                    [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', $w, '@@')], 
                    $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
            }
        }
        if(count($result['title']) > 0 ){
            $this->mergeResults($result['title']);
        }
        if(count($result['description']) > 0) {
            $this->mergeResults($result['description']);
        }
        
        if(count($this->searchData) > 0) {
           $this->filterTitleDescription(); 
        }
    }
    
    
    private function mergeResults(array $data) {
        foreach($data as $d) {
            $this->searchData[$d->id][] = $d;
        }
    }
    
    private function filterTitleDescription() {
        
        foreach ($this->searchData as $result) {
            $obj = new \stdClass();
            foreach ($result as $res) {
                if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle') {
                    $obj->title = $res->value;
                    $obj->language = $res->lang;
                    $obj->id = $res->id;
                }
                if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription') {
                     $obj->description = $res->value;
                     $obj->id = $res->id;
                     $obj->language = $res->lang;
                }
                if($res->property  == 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type') {
                    $obj->property = $res->value;
                }
                if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate') {
                    $obj->avdate = $res->value;
                }
                if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasAccessRestriction') {
                    $obj->accesres = $res->value;
                }
                if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitleImage') {
                    $obj->titleimage = $res->value;
                } 
            }
            $this->sqlResult[] = $obj;
        }
    }
}
