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
    private $orderby;
    private $orderby_column;
    /* ordering */
    
    public function __construct()
    {
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
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc", object $metavalue = null): array
    {
       
        //helper function to create object from the metavalue string
        $this->metaObj = $metavalue;
        //init the values for the paging
        $this->initPaging($limit, $page, $order);
        $count = 0;
        //user selected words and type
        if (isset($this->metaObj->words)) {
            $count = $this->countWordsFromDb();
            if ((int)$count > 0) {
                $this->getWordsFromDb();
            }
        } elseif (isset($this->metaObj->years)) {
            $count = $this->countYearsFromDb();
            if ((int)$count > 0) {
                $this->getYearsFromDb();
            }
        } elseif (isset($this->metaObj->type)) {
            $count = $this->countTypesFromDb();
            if ((int)$count > 0) {
                //user selected just type
                $this->getTypesFromDB();
            }
        }
        
        if ($this->sqlResult == null) {
            $this->sqlResult = array();
        }
        return array('count' => $count, 'data' => $this->sqlResult);
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
     * Count the words defined from the search input fields
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
        
        $queryStr = "
                SELECT 
                id
                from gui.search_count_words_view_func('".$wordStr."', '".$this->siteLang."' ";
        
        if (!empty($typeStr)) {
            $queryStr .= ", ".$typeStr." ";
        } else {
            $queryStr .= ", ARRAY[]::text[] ";
        }
        
        if (!empty($yearsStr)) {
            $queryStr .= ", ".$yearsStr."); ";
        } else {
            $queryStr .= "); ";
        }
        
        try {
            $query = $this->repodb->query($queryStr);
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
    
    private function countYearsFromDb(): int
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsFilter();
        $return = 0;
        
        $queryStr = "
            SELECT 
            id
            from gui.search_count_years_view_func('".$yearsStr."', '".$this->siteLang."' ";
        
        if (!empty($typeStr)) {
            $queryStr .= ", ".$typeStr." );";
        } else {
            $queryStr .= ", ARRAY[]::text[] );";
        }
        
        try {
            $query = $this->repodb->query($queryStr);
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
        
        $queryStr = "
                SELECT 
                id
                from gui.search_count_types_view_func(";
        
        if (!empty($typeStr)) {
            $queryStr .= " ".$typeStr." ";
        } else {
            $queryStr .= " ARRAY[]::text[] ";
        }
        
        $queryStr .= ", '".$this->siteLang."'";
        
        if (!empty($yearsStr)) {
            $queryStr .= ", ".$yearsStr."); ";
        } else {
            $queryStr .= "); ";
        }
        
        try {
            $query = $this->repodb->query($queryStr);
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
       
        $queryStr = "
            SELECT 
            *
            from gui.search_words_view_func('".$wordStr."', '".$this->siteLang."', "
                . "'".$this->limit."', ' ".$this->offset."', '".$this->orderby."',"
                . " '".$this->orderby_column."' ";

        if (!empty($typeStr)) {
            $queryStr .= ", ".$typeStr." ";
        } else {
            $queryStr .= ", ARRAY[]::text[]  ";
        }
        
        if (!empty($yearsStr)) {
            $queryStr .= ", ".$yearsStr."); ";
        } else {
            $queryStr .= "); ";
        }
        
        try {
            $query = $this->repodb->query($queryStr);
            
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
     *
     *
     * @return type
     */
    private function getTypesFromDB()
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsArrayFilter();
        
        $queryStr = "
                select * 
                from 
                gui.search_types_view_func(";
        
        if (!empty($typeStr)) {
            $queryStr .= " ".$typeStr." ";
        } else {
            $queryStr .= " ARRAY[]::text[] ";
        }
        
        $queryStr .= ", '".$this->siteLang."', '".$this->limit."', "
                . "'".$this->offset."', '".$this->orderby."', "
                . "'".$this->orderby_column."' ";
        
        if (!empty($yearsStr)) {
            $queryStr .= ", ".$yearsStr."); ";
        } else {
            $queryStr .= "); ";
        }
                
        try {
            $query = $this->repodb->query($queryStr);
            
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
    
    
    private function getYearsFromDB()
    {
        $typeStr = $this->formatTypeFilter();
        $yearsStr = $this->formatYearsFilter();
        $return = 0;
        //select * from gui.search_years_view_func('2015 or 2016', 'en', '100', '0', 'asc', 'title', ARRAY['https://vocabs.acdh.oeaw.ac.at/schema#Resource']);
        
        $queryStr = "
            SELECT 
            *
            from gui.search_years_view_func('".$yearsStr."', '".$this->siteLang."', "
                . "'".$this->limit."', ' ".$this->offset."', '".$this->orderby."',"
                . " '".$this->orderby_column."' ";

        if (!empty($typeStr)) {
            $queryStr .= " , ".$typeStr." );";
        } else {
            $queryStr .= ", ARRAY[]::text[] ); ";
        }
       
        try {
            $query = $this->repodb->query($queryStr);
            
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
