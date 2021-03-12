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
class SearchViewModel extends ArcheModel
{
    protected $repodb;
    private $config;
    private $repo;
    private $repolibDB;
    private $sqlResult;
    private $siteLang;
    private $searchCfg;
    private $metaObj;
    private $log;
    /* ordering */
    private $limit;
    private $offset;
    private $orderby;
    private $orderby_column;
    private $binarySearch = false;
    private $namespace;
    /* ordering */
    
    public function __construct()
    {
        //set up the DB connections
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        
        $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        
        $this->searchCfg = new \acdhOeaw\acdhRepoLib\SearchConfig();
        $this->repolibDB = \acdhOeaw\acdhRepoLib\RepoDb::factory(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml', 'guest');
        $this->metaObj = new \stdClass();
        $this->log = new \zozlak\logging\Log(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/zozlaklog', \Psr\Log\LogLevel::DEBUG);
        (isset($this->repo->getSchema()->__get('namespaces')->ontology)) ? $this->namespace = $this->repo->getSchema()->__get('namespaces')->ontology : $this->namespace = 'https://vocabs.acdh.oeaw.ac.at/schema#';
    }
    
    private function setUpPayload(): void
    {
        if (isset($this->metaObj->payload)) {
            $this->binarySearch = $this->metaObj->payload;
        }
    }
    
    /**
     * Full content search
     *
     * @param int $limit
     * @param int $page
     * @param string $order
     * @param object $metavalue
     * @return array
     */
    public function getViewData_V2(int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null): array
    {
        $result = array();
        $this->metaObj = $metavalue;
        $this->initPaging($limit, $page, $order);
        $sqlYears = $this->formatYearsFilter_V2();
        $sqlTypes = $this->formatTypeFilter_V2();
        if (isset($this->metaObj->words) && (count($this->metaObj->words) > 0)) {
            $sqlWords = implode(" & ", $this->metaObj->words);
        } else {
            $sqlWords = (string)"*";
        }
        
        $this->setUpPayload();
        
        try {
            $this->setSqlTimeout('30000');
            //"select * from gui.search_full_func('Wollmilchsau', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'], '%(2020|1997)%', 'en', '10', '0', 'desc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle');"
            $query = $this->repodb->query(
                "select * from gui.search_full_func(:wordStr, ".$sqlTypes.", :yearStr, :lang, :limit, :offset, :order, :order_prop, :binarySearch);",
                array(
                    ':wordStr' => (string)$sqlWords,
                    ':yearStr' => (string)$sqlYears,
                    ':lang' => $this->siteLang,
                    ':limit' => $this->limit,
                    ':offset' => $this->offset,
                    ':order' => $this->orderby,
                    ':order_prop' => $this->orderby_column,
                    ':binarySearch' => $this->binarySearch
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            error_log($ex->getMessage());
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
       
        if ($this->sqlResult == null) {
            $this->sqlResult = array();
        }
        if (isset($this->sqlResult[0]->cnt)) {
            $cnt = $this->sqlResult[0]->cnt;
        } else {
            $cnt = 0;
        }
        
        return array('count' => $cnt, 'data' => $this->sqlResult);
    }
    
    
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null): array
    {
        $result = array();
        $this->metaObj = $metavalue;
        $this->initPaging($limit, $page, $order);
        $sqlYears = $this->formatYearsFilter_V2();
        $sqlTypes = $this->formatTypeFilter_V2();
        if (isset($this->metaObj->words) && (count($this->metaObj->words) > 0)) {
            $sqlWords = implode(" & ", $this->metaObj->words);
        } else {
            $sqlWords = (string)"*";
        }
        
        $this->setUpPayload();
        
        try {
            $this->setSqlTimeout('30000');
            //"select * from gui.search_full_func('Wollmilchsau', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'], '%(2020|1997)%', 'en', '10', '0', 'desc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle');"
            $query = $this->repodb->query(
                "select * from gui.search_full_v2_func(:wordStr, ".$sqlTypes.", :yearStr, :lang, :limit, :offset, :order, :order_prop, :binarySearch);",
                array(
                    ':wordStr' => (string)$sqlWords,
                    ':yearStr' => (string)$sqlYears,
                    ':lang' => $this->siteLang,
                    ':limit' => $this->limit,
                    ':offset' => $this->offset,
                    ':order' => $this->orderby,
                    ':order_prop' => $this->orderby_column,
                    ':binarySearch' => $this->binarySearch
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            error_log($ex->getMessage());
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
       
        if ($this->sqlResult == null) {
            $this->sqlResult = array();
        }
        if (isset($this->sqlResult[0]->cnt)) {
            $cnt = $this->sqlResult[0]->cnt;
        } else {
            $cnt = 0;
        }
        
        return array('count' => $cnt, 'data' => $this->sqlResult);
    }
    
    
    /**
     * Change the years format for the sql query
     *
     * @return string
     */
    private function formatYearsFilter_V2(): string
    {
        //%(2020|1997)%
        $yearsStr = "%";
        if (isset($this->metaObj->years)) {
            $yearsStr = '%(';
            $i = 0;
            $len = count($this->metaObj->years);
            if ($len > 0) {
                foreach ($this->metaObj->years as $y) {
                    if ($i == $len - 1) {
                        // last
                        $yearsStr .= $y.')%';
                    } else {
                        $yearsStr .= $y.'|';
                    }
                    $i++;
                }
            } else {
                $yearsStr = "%";
            }
        }
        return $yearsStr;
    }
    
    private function formatYearsFilter(): string
    {
        $yearsStr = "";
        if (isset($this->metaObj->years)) {
            $count = count($this->metaObj->years);
            if ($count > 0) {
                $i = 0;
                foreach ($this->metaObj->years as $y) {
                    $yearsStr .= $y;
                    if ($count - 1 != $i) {
                        $yearsStr .= ' or ';
                    }
                    $i++;
                }
            } else {
                $yearsStr = "";
            }
        }
        return $yearsStr;
    }
    
    private function formatTypeFilter_V2(): string
    {
        $typeStr = "ARRAY[]::text[]";
        if (isset($this->metaObj->type)) {
            $count = count($this->metaObj->type);
            if ($count > 0) {
                $typeStr = 'ARRAY [ ';
                $i = 0;
                foreach ($this->metaObj->type as $t) {
                    $typeStr .= "'https://vocabs.acdh.oeaw.ac.at/schema#$t'";
                    if ($count - 1 != $i) {
                        $typeStr .= ', ';
                    } else {
                        $typeStr .= ' ]';
                    }
                    $i++;
                }
            } else {
                $typeStr = "ARRAY[]::text[]";
            }
        }
        return $typeStr;
    }
    
    private function formatTypeFilter(): string
    {
        $typeStr = "";
        if (isset($this->metaObj->type)) {
            $count = count($this->metaObj->type);
            if ($count > 0) {
                $typeStr .= 'ARRAY [ ';
                $i = 0;
                foreach ($this->metaObj->type as $t) {
                    $typeStr .= "'https://vocabs.acdh.oeaw.ac.at/schema#$t'";
                    if ($count - 1 != $i) {
                        $typeStr .= ', ';
                    } else {
                        $typeStr .= ' ]';
                    }
                    $i++;
                }
            } else {
                $typeStr = "";
            }
        }
        return $typeStr;
    }
    
    private function formatYearsArrayFilter(): string
    {
        $yearsStr = "";
        if (isset($this->metaObj->years)) {
            $count = count($this->metaObj->years);
            if ($count > 0) {
                $yearsStr .= "'";
                $i = 0;
                foreach ($this->metaObj->years as $y) {
                    $yearsStr .= "$y%";
                    if ($count - 1 != $i) {
                        $yearsStr .= '|';
                    } else {
                        $yearsStr .= "'";
                    }
                    $i++;
                }
            } else {
                $yearsStr = "";
            }
        }
        return $yearsStr;
    }
    
    /**
     * Count the words + rdf:type + years defined from the search input fields
     *
     * @return int
     */
    private function countWordsFromDb(): int
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsArrayFilter();
        $wordStr = '';
        $return = 0;
        foreach ($this->metaObj->words as $w) {
            $wordStr .= $w.' ';
        }
        
        if (empty($typeStr)) {
            $typeStr = "ARRAY[]::text[]";
        }
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "SELECT 
                id
                from gui.search_count_words_view_func(:wordStr, :lang, ".$typeStr.", :yearStr)",
                array(
                    ':wordStr' => $wordStr,
                    ':lang' => $this->siteLang,
                   // ':typeStr' => $typeStr,
                    ':yearStr' => $yearsStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $return = $query->fetch();
            $this->changeBackDBConnection();
            if (isset($return->id)) {
                return (int)$return->id;
            }
            return 0;
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
    }
    
    /**
     * COunt the years + rdf:type selected values
     * @return int
     */
    private function countYearsFromDb(): int
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsFilter();
        $return = 0;
        
        if (empty($typeStr)) {
            $typeStr = "ARRAY[]::text[]";
        }
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "SELECT 
                id
                from gui.search_count_years_view_func(:yearStr, :lang, ".$typeStr.")",
                array(
                    ':yearStr' => $yearsStr,
                    ':lang' => $this->siteLang
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $return = $query->fetch();
            $this->changeBackDBConnection();
            if (isset($return->id)) {
                return (int)$return->id;
            }
            return 0;
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
    }
    
    /**
     * Count the types defined from the search input fields
     *
     * @return int
     */
    private function countTypesFromDB(): int
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsArrayFilter();
        
        if (empty($typeStr)) {
            $typeStr = "ARRAY[]::text[]";
        }
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "
                SELECT 
                id
                from gui.search_count_types_view_func(".$typeStr.", :lang,  :yearStr)",
                array(
                    ':lang' => $this->siteLang,
                    ':yearStr' => $yearsStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $return = $query->fetch();
            $this->changeBackDBConnection();
            if (isset($return->id)) {
                return (int)$return->id;
            }
            return 0;
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
    }
    
    /**
     * Collect the words and/or type/years keywords
     *
     * @return type
     */
    private function getWordsFromDB()
    {
        //pdo Invalid text representation for text array -> try to find solution
        $typeStr = $this->formatTypeFilter();
        $wordStr = '';
        foreach ($this->metaObj->words as $w) {
            $wordStr .= $w.' ';
        }
        $yearsStr = $this->formatYearsArrayFilter();
       
        if (empty($typeStr)) {
            $typeStr = "ARRAY[]::text[]";
        }
        //(_searchstr text, _lang text DEFAULT 'en', _limit text DEFAULT '10',
        //_page text DEFAULT '0', _orderby text DEFAULT 'desc', _orderby_prop text DEFAULT 'avdate',
        //_rdftype text[] DEFAULT '{}', _acdhyears text DEFAULT '')
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "SELECT 
                *
                from gui.search_words_view_func(
                :wordStr, :lang, :limit, :offset, 
                :orderby, :orderby_column, ".$typeStr.", :yearStr)",
                array(
                    ':wordStr' => $wordStr,
                    ':lang' => $this->siteLang,
                    ':limit' => $this->limit,
                    ':offset' => $this->offset,
                    ':orderby' => $this->orderby,
                    ':orderby_column' => $this->orderby_column,
                    ':yearStr' => $yearsStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
    }
    
    /**
     * Get the type + year resources from the DB
     *
     * @return type
     */
    private function getTypesFromDB()
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsArrayFilter();
        
        if (empty($typeStr)) {
            $typeStr = " ARRAY[]::text[] ";
        }
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "SELECT 
                *
                from gui.search_types_view_func(
                ".$typeStr.", :lang, :limit, :offset, 
                :orderby, :orderby_column, :yearStr)",
                array(
                    ':lang' => $this->siteLang,
                    ':limit' => $this->limit,
                    ':offset' => $this->offset,
                    ':orderby' => $this->orderby,
                    ':orderby_column' => $this->orderby_column,
                    ':yearStr' => $yearsStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
    }
    
    
    /**
     * Get the years from the database
     *
     * @return type
     */
    private function getYearsFromDB()
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsFilter();
        $return = 0;
        //select * from gui.search_years_view_func('2015 or 2016', 'en', '100', '0', 'asc', 'title', ARRAY['https://vocabs.acdh.oeaw.ac.at/schema#Resource']);
   
        if (empty($typeStr)) {
            $typeStr = "ARRAY[]::text[]";
        }
        
        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "SELECT 
                *
                from gui.search_years_view_func(
                :yearStr, :lang, :limit, :offset, 
                :orderby, :orderby_column, ".$typeStr.")",
                array(
                    ':lang' => $this->siteLang,
                    ':limit' => $this->limit,
                    ':offset' => $this->offset,
                    ':orderby' => $this->orderby,
                    ':orderby_column' => $this->orderby_column,
                    ':yearStr' => $yearsStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_CLASS);
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
    }
    
    private function initPaging(int $limit, int $page, string $order)
    {
        $this->limit = $limit;
        ($page == 0 || $page == 1) ? $this->offset = 0 : $this->offset = $limit * ($page -1);
        
        switch ($order) {
            case 'dateasc':
                $this->orderby = "asc";
                $this->orderby_column = "avdate";
                break;
            case 'datedesc':
                $this->orderby = "desc";
                $this->orderby_column = "avdate";
                break;
            case 'titleasc':
                $this->orderby = "asc";
                $this->orderby_column = "title";
                break;
            case 'titledesc':
                $this->orderby = "desc";
                $this->orderby_column = "title";
                break;
            default:
                $this->orderby = "desc";
                $this->orderby_column = "avdate";
        }
    }
}
