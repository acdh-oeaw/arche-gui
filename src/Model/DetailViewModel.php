<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class DetailViewModel extends ArcheModel {
    
    private $repodb;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }
    
    /**
     * Get the detail view data from DB
     * 
     * @param string $identifier
     * @return array
     */
    public function getViewData(string $identifier = ""): array {
        if(empty($identifier)) { return array();}
        $result = array();
        try {
            //run the actual query
            $query = $this->repodb->query(" select * from detail_view_func(:id) ", array(':id' => $identifier));
            $result = $query->fetchAll();
        } catch (Exception $ex) {
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            $result = array();
        }
        
        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * Get the breadcrumb data for the detail view 
     * 
     * @param string $identifier
     * @return array
     */
    public function getBreadCrumbData(string $identifier = ''): array {
        if(empty($identifier)) { return array();}
        
        $result = array();
        try {
            //run the actual query
            $query = $this->repodb->query(" select * from breadcrumb_view_func(:id) order by depth desc ", array(':id' => $identifier));
            $result = $query->fetchAll();
        } catch (Exception $ex) {
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            $result = array();
        }
        $this->changeBackDBConnection();
        return $result;
    }
        
}