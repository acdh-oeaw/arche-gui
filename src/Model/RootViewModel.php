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
    public function getViewData(string $limit = "10", string $page = "0", string $order = "datedesc"): array {
        
        if($page > 0) {
            $page = $limit * $page;
        }
        $order = $this->ordering($order);
        try {
            
            $query = $this->repodb->query("
                SELECT 
                DISTINCT(rvf.id),
                (select te.title from gui.root_views_func() as te  where te.id = rvf.id and te.language='en' limit 1) as title_en,
                (select td.title from gui.root_views_func() as td  where td.id = rvf.id and td.language='de' limit 1) as title_de,
                (select ted.description from gui.root_views_func() as ted  where ted.id = rvf.id and ted.language='en' limit 1) as desc_en,
                (select tdd.description from gui.root_views_func() as tdd  where tdd.id = rvf.id and tdd.language='de' limit 1) as desc_de,
                rvf.avDate, rvf.accresres, rvf.titleimage
                from gui.root_views_func() as rvf 
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
            $query = $this->repodb->query("select count(DISTINCT(id)) from gui.root_views_func()");
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
