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
    private $log;
    /* ordering */
    private $limit;
    private $offset;
    private $order;
    /* ordering */
    
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
            $this->getWordsFromDB();
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
    
    
    private function getWordsFromDB() {
        $word = $this->metaObj->words[0];
         
        try {
            
            $query = $this->repodb->query("
                SELECT 
                *
                from gui.search_words_view_func('".$word."', '".$this->siteLang."') 
                order by ".$this->order." limit ".$this->limit." offset ".$this->offset."
                ;");
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
           
        } catch (Exception $ex) {
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            return array();
        }
    }
    
    private function getTypesFromDB() {
        $type = $this->metaObj->type;
        $str = "";
        $count = count($type);
        foreach($type as $k => $t){
            $str .= "'https://vocabs.acdh.oeaw.ac.at/schema#$t'";
            if($count != $k) {
                $str .= ', ';
            }
        }
        
        try {
            $query = $this->repodb->query("
                select * 
                from 
                gui.search_types_view_func(
                ARRAY[".$str."]
                , '".$this->siteLang."') 
                order by ".$this->order." limit ".$this->limit." offset ".$this->offset."
                ;");
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
           
        } catch (Exception $ex) {
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            return array();
        }
    }
    
    private function yearsSqlString() {
        $str = " WITH ids AS (
                SELECT id FROM ((
            SELECT DISTINCT id
            FROM metadata
            WHERE property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' AND value_t::date >= '2020-01-01'
        ) t0 JOIN (
            SELECT DISTINCT id
            FROM metadata
            WHERE property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate' AND value_t::date <= '2020-02-01'
        ) t1 USING (id) ) t1   LIMIT '10' OFFSET '10'
            )
            
                    SELECT id, property, type, lang, value
                    FROM metadata JOIN ids USING (id)
                  UNION
                    SELECT id, null, 'ID' AS type, null, ids AS VALUE 
                    FROM identifiers JOIN ids USING (id)
                  UNION
                    SELECT id, property, 'REL' AS type, null, target_id::text AS value
                    FROM relations JOIN ids USING (id)
                
            UNION
            SELECT id, 'search://match'::text AS property, 'http://www.w3.org/2001/XMLSchema#boolean'::text AS type, ''::text AS lang, 'true'::text AS value FROM ids";
    }
    
    private function wordandtypelString() {
        $str = "WITH ids AS (
                SELECT id FROM ((
            SELECT DISTINCT id 
            FROM full_text_search 
            WHERE websearch_to_tsquery('simple', 'Wollmilchsau') @@ segments  AND property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription'
        ) t0 JOIN (
            SELECT DISTINCT id
            FROM metadata
            WHERE property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' AND substring(value, 1, 1000) = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
        
              UNION
                SELECT DISTINCT id
                FROM (SELECT id, 'https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier' AS property, '' AS lang, ids AS value FROM identifiers) t
                WHERE property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' AND substring(value, 1, 1000) = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
              UNION
                SELECT DISTINCT id
                FROM (SELECT id, property, '' AS lang, 'https://repo.hephaistos.arz.oeaw.ac.at/api/' || target_id AS value FROM relations) t
                WHERE property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' AND substring(value, 1, 1000) = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'
            ) t1 USING (id) ) t1   LIMIT '10' OFFSET '0'
            )
            
                    SELECT id, property, type, lang, value
                    FROM metadata JOIN ids USING (id)
                  UNION
                    SELECT id, null, 'ID' AS type, null, ids AS VALUE 
                    FROM identifiers JOIN ids USING (id)
                  UNION
                    SELECT id, property, 'REL' AS type, null, target_id::text AS value
                    FROM relations JOIN ids USING (id)
                
            UNION
            SELECT id, 'search://match'::text AS property, 'http://www.w3.org/2001/XMLSchema#boolean'::text AS type, ''::text AS lang, 'true'::text AS value FROM ids";
    }
    
    private function binariesSqlString() {
        $str = "WITH ids AS (
                SELECT id FROM (
            SELECT DISTINCT id 
            FROM full_text_search 
            WHERE websearch_to_tsquery('simple', 'Wollmilchsau') @@ segments  AND property = 'BINARY'
        ) t1   LIMIT '10' OFFSET '0'
            )
            
                    SELECT id, property, type, lang, value
                    FROM metadata JOIN ids USING (id)
                  UNION
                    SELECT id, null, 'ID' AS type, null, ids AS VALUE 
                    FROM identifiers JOIN ids USING (id)
                  UNION
                    SELECT id, property, 'REL' AS type, null, target_id::text AS value
                    FROM relations JOIN ids USING (id)
                
            UNION
            SELECT id, 'search://match'::text AS property, 'http://www.w3.org/2001/XMLSchema#boolean'::text AS type, ''::text AS lang, 'true'::text AS value FROM ids";
    }
    
    
    
    
    
    
    
    
    
    
    
    private function initPaging(int $limit, int $page, string $order) {
        $this->limit = $limit;
        ($page == 0 || $page == 1) ? $this->offset = 0 : $this->offset = $limit * ($page -1);
        
        switch ($order) {
            case 'dateasc':
                $this->order = "avdate asc";
                break;
            case 'datedesc':
                $this->order = "avdate desc";
                break;
            case 'titleasc':
                $this->order = "title asc";
                break;
            case 'titledesc':
                $this->order = "title desc";
                break;
            default:
                $this->order = "avdate desc";
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
