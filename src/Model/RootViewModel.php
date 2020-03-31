<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
/**
 * Description of RootModel
 *
 * @author nczirjak
 */
class RootViewModel extends ArcheModel {
    
    private $repodb;
    private $sqlResult;
    private $siteLang = 'en';
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    /**
     * The ordering for the root sql
     * 
     * @param type $order
     * @return string
     */
    private function ordering($order = "datedesc"): string {
        
        switch ($order) {
            case 'dateasc':
                $order = "avdate asc";
                break;
            case 'datedesc':
                $order = "avdate desc";
                break;
            case 'titleasc':
                $order = "title asc";
                break;
            case 'titledesc':
                $order = "title desc";
                break;
            default:
                $order = "avdate desc";
        }
        return $order;
    }
        
    /**
     * get the root views data
     * 
     * @return array
     */
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc"): array {
        
        if($page > 0) {
            $page = $limit * $page;
        }
        $order = $this->ordering($order);
        try {
            
            $query = $this->repodb->query("
                SELECT 
                *
                from gui.root_views_func('".$this->siteLang."') 
                where title is not null 
                order by ".$order." limit ".$limit." offset ".$page.";");
            
            $this->sqlResult = $query->fetchAll();
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            return array();
        }
        return $this->sqlResult;
    }
    
    /**
     * Count the actual root resources
     * @return int
     */
    public function countRoots(): int {
        $result = array();
        try {
            $query = $this->repodb->query("select count(DISTINCT(id)) from gui.root_views_func() where title is not null  ");
            $this->sqlResult = $query->fetch();
            $this->changeBackDBConnection();
            if(isset($this->sqlResult->count)) {
                return $this->sqlResult->count;
            }    
        } catch (Exception $ex) {
            error_log(print_r($ex->getMessage(), true));
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            error_log(print_r($ex->getMessage(), true));
            return 0;
        }
        return 0;
    }
}
