<?php

namespace Drupal\acdh_repo_gui\Model;

/**
 * Description of DashboardModel
 *
 * @author norbertczirjak
 */
class DashboardModel extends ArcheModel {
    
    private $repodb;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    /**
     * Generate the sql data
     * @return array
    */
    public function getViewData(): array {
        
        $queryStr = "
            SELECT 
                property, count(*) as cnt
            from public.metadata 
            group by property";
        
        try {
            $query = $this->repodb->query($queryStr);
            $return = $query->fetchAll();
            
            $this->changeBackDBConnection();
            return $return;
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
        
    }
    
}
