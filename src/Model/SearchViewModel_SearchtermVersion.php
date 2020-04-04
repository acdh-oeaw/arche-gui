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
class SearchViewSearchtermModel extends ArcheModel {
    
    private $repodb;
    private $repolibDB;
    private $sqlResult;
    private $siteLang;
    private $searchCfg;
    private $metaObj;
    private $searchData = array();
    private $log;
    private $limit;
    private $offset;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->searchCfg = new \acdhOeaw\acdhRepoLib\SearchConfig();
        $this->repolibDB = \acdhOeaw\acdhRepoLib\RepoDb::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml', 'guest');
        $this->metaObj = new \stdClass();
        $this->log = new \zozlak\logging\Log(drupal_get_path('module', 'acdh_repo_gui').'/zozlaklog', \Psr\Log\LogLevel::DEBUG);
    }
    
     /**
     * get the search view data
     * 
     * @return array
     */
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null): array {
       
        //helper function to create object from the metavalue string
        $this->metaObj = $metavalue;
        //init the values for the paging
        $this->initPaging($limit, $page, $order);
        
        //user selected words and type
        if(isset($this->metaObj->words) && isset($this->metaObj->type)) {
            $this->getWordsFromDB($this->metaObj);
        } else if(isset($this->metaObj->type)) {
            //user selected just type
            $this->getTypesFromDB();
        } else if(isset($this->metaObj->words)) {
            //user selected just words
            $this->getWordsFromDB();
        }
        
        //we have year
        if(isset($metavalue->years)) {
            //user just selected the years
            if(!isset($this->metaObj->words) && !isset($this->metaObj->type)) {
                $this->getYearsFromDB();
            } else {
                
            }
                
            echo 'we have years';
        }
        //$this->searchCfg->ftsQuery             = "Wollmilchsau";
        //
        if($this->sqlResult == null ) {
            $this->sqlResult = array();
        }
        return $this->sqlResult;
    }
    
    
    private function initPaging(int $limit, int $page, string $order) {
        $this->limit = $limit;
        ($page == 0 || $page == 1) ? $this->offset = 0 : $this->offset = $limit * ($page -1);
    }
    
    /**
     * Get just the years from the db
    */
    private function getYearsFromDB() {
        $this->searchCfg->metadataMode = RepoResourceInterface::META_RESOURCE; 
        echo $this->searchCfg->limit = $this->limit;
        echo $this->searchCfg->offset = $this->offset;
        $result = array();
        $this->repolibDB->setQueryLog($this->log);
        foreach ($this->metaObj->years as $y) {
            $result = $this->repolibDB->getPdoStatementBySearchTerms(
                [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate', '2020-01-01', '>=', \zozlak\RdfConstants::XSD_DATE), 
                    new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate', '2020-02-01', '<=', \zozlak\RdfConstants::XSD_DATE)], 
                $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
        }
        
       
        if(count($result) > 0 ){
            $this->mergeResults($result);
        }
        
        if(count($this->searchData) > 0) {
           $this->filterTitleDescription(); 
        }
    }
    
    
    /**
     * Get just the types from the db
     */
    private function getTypesFromDB() {
        $this->searchCfg->metadataMode = RepoResourceInterface::META_RESOURCE; 
        echo $this->searchCfg->limit = $this->limit;
        echo $this->searchCfg->offset = $this->offset;
        
        $result = array();
        foreach ($this->metaObj->type as $t) {
            
            $result = $this->repolibDB->getPdoStatementBySearchTerms(
                [new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=')], 
                $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
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
        echo $this->searchCfg->limit = $this->limit;
        echo $this->searchCfg->offset = $this->offset;
        $title = array();
        $description = array();
        if( isset($types) && count($types->type) > 0) {
            foreach($this->metaObj->type as $t) {
                foreach ($this->metaObj->words as $w) {
                    
                    $title[] = $this->repolibDB->getPdoStatementBySearchTerms(
                        [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', $w, '@@'), new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=' )], 
                        $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
                    
                    $description [] = $this->repolibDB->getPdoStatementBySearchTerms(
                        [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', $w, '@@'), new SearchTerm('http://www.w3.org/1999/02/22-rdf-syntax-ns#type', 'https://vocabs.acdh.oeaw.ac.at/schema#'.$t, '=' )], 
                        $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
                }   
            }
        } else {
            foreach ($this->metaObj->words as $w) {
                $title[] =  $this->repolibDB->getPdoStatementBySearchTerms(
                    [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', $w, '@@')], 
                    $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
                $description [] =  $this->repolibDB->getPdoStatementBySearchTerms(
                    [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', $w, '@@')], 
                    $this->searchCfg)->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_CLASS);
            }
        }
        
       
        if(count($title) > 0 ){
            $this->mergeResults($title);
        }
        if(count($description) > 0) {
            $this->mergeResults($description);
        }
       
        if(count($this->searchData) > 0) {
           $this->filterTitleDescription(); 
        } else {
            $this->sqlResult = array();
        }
        
        
    }
    
    private function mergeResults(array $data) {
        foreach($data as $arr) {
            foreach($arr as $k => $v) {
                $this->searchData[$k] = $v;
            }
        }
    }
    
    private function filterTitleDescription() {
        
        foreach ($this->searchData as $result) {
           
            
            $obj = new \stdClass();
            foreach ($result as $key => $res) {
                
                    if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle') {
                    $obj->title = $res->value;
                    $obj->language = $res->lang;
                    $obj->id = $key;
                    }
                    if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription') {
                         $obj->description = $res->value;
                         $obj->id = $key;
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
