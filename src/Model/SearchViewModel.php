<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
use acdhOeaw\arche\lib\Repo;

/**
 * Description of SearchViewModel
 *
 * @author nczirjak
 */
class SearchViewModel extends ArcheModel
{
    private $repolibDB;
    private $sqlResult;
    private $siteLang;
    private $searchCfg;
    private $log;
    private $sqlParams;
    /* ordering */
    protected $limit;
    protected $offset;
    protected $orderby;
    protected $orderby_column;
    protected $binarySearch = false;
    protected $namespace;
    /* ordering */
    
    public function __construct()
    {
        //set up the DB connections
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        
        $this->searchCfg = new \acdhOeaw\arche\lib\SearchConfig();
        $this->repolibDB = \acdhOeaw\arche\lib\RepoDb::factory(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml', 'guest');
        
        $this->log = new \zozlak\logging\Log(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/zozlaklog', \Psr\Log\LogLevel::DEBUG);
        (isset($this->repo->getSchema()->__get('namespaces')->ontology)) ? $this->namespace = $this->repo->getSchema()->__get('namespaces')->ontology : $this->namespace = 'https://vocabs.acdh.oeaw.ac.at/schema#';
    }
    
    private function setUpPayload(): void
    {
        if (isset($this->sqlParams['payload'])) {
            $this->binarySearch = $this->sqlParams['payload'][0];
        }
    }
    
    public function getVcr(array $params = []): array
    {
        if (count($params) === 0) {
            return array();
        }
        
        $this->sqlParams = $params;
        $this->initPaging();
        $sqlYears = $this->formatYearsFilter();
        $sqlTypes = $this->formatTypeFilter();
        $sqlCategory = $this->formatTypeFilter("category");
        $sqlWords = $this->formatWordsFilter();
        $this->setUpPayload();
        
        
        try {
            $this->setSqlTimeout('60000');
            //"select * from gui.search_full_func('Wollmilchsau', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'], '%(2020|1997)%', 'en', '10', '0', 'desc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle');"
            $query = $this->repodb->query(
                "SELECT 
                    json_agg(
                        json_build_object(
                            'uri', fs.pid,
                            'label', fs.title,
                            'description', fs.description                            
                        )
                    )
                from gui.search_full_v3_func(:wordStr, ".$sqlTypes.", :yearStr, :lang, :limit, :offset, :order, :order_prop, :binarySearch, ".$sqlCategory.") as fs;",
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
            $this->sqlResult = $query->fetchAll();
            $this->changeBackDBConnection();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
        
        return $this->sqlResult;
    }
    
    //int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null
    public function getViewData(array $params = []): array
    {
        if (count($params) === 0) {
            return array();
        }
        
        $this->sqlParams = $params;
        $this->initPaging();
        $sqlYears = $this->formatYearsFilter();
        $sqlTypes = $this->formatTypeFilter();
        $sqlCategory = $this->formatTypeFilter("category");
        $sqlWords = $this->formatWordsFilter();
        $this->setUpPayload();
        
        try {
            $this->setSqlTimeout('60000');
            //"select * from gui.search_full_func('Wollmilchsau', ARRAY [ 'https://vocabs.acdh.oeaw.ac.at/schema#Collection'], '%(2020|1997)%', 'en', '10', '0', 'desc', 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle');"
            $query = $this->repodb->query(
                "select * from gui.search_full_v3_func(:wordStr, ".$sqlTypes.", :yearStr, :lang, :limit, :offset, :order, :order_prop, :binarySearch, ".$sqlCategory.");",
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
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
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
    
    private function formatWordsFilter(): string
    {
        if (isset($this->sqlParams['words']) && (count((array)$this->sqlParams['words']) > 0)) {
            return implode(" & ", (array)$this->sqlParams['words']);
        }
        return (string)"*";
    }
    
    /**
     * Change the years format for the sql query
     *
     * @return string
     */
    private function formatYearsFilter(): string
    {
        //%(2020|1997)%
        $yearsStr = "%";
        if (isset($this->sqlParams['years'])) {
            $yearsStr = '%(';
            $i = 0;
            $len = count($this->sqlParams['years']);
            if ($len > 0) {
                foreach ($this->sqlParams['years'] as $y) {
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
    
  
    private function formatTypeFilter(string $key = "type"): string
    {
        $typeStr = "ARRAY[]::text[]";
        if (isset($this->sqlParams[$key]) && !empty($this->sqlParams[$key][0])) {
            $count = count($this->sqlParams[$key]);
            if ($count > 0) {
                $typeStr = 'ARRAY [ ';
                $i = 0;
                foreach ($this->sqlParams[$key] as $t) {
                    $typeStr .= "'$t'";
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
        } catch (\Exception $ex) {
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
        } catch (\Exception $ex) {
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
        } catch (\Exception $ex) {
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
        } catch (\Exception $ex) {
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
        } catch (\Exception $ex) {
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
    
    private function initPaging()
    {
        $this->limit = $this->sqlParams['limit'][0];
        ($this->sqlParams['page'][0] == 0 || $this->sqlParams['page'][0]== 1) ? $this->offset = 0 : $this->offset = (int)$this->sqlParams['limit'][0] * ((int)$this->sqlParams['page'][0] -1);
        
        switch ($this->sqlParams['order'][0]) {
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
            case 'typeasc':
                $this->orderby = "asc";
                $this->orderby_column = "type";
                break;
            case 'typedesc':
                $this->orderby = "desc";
                $this->orderby_column = "type";
                break;
            default:
                $this->orderby = "desc";
                $this->orderby_column = "avdate";
        }
    }
}
