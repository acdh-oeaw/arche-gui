<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DisseminationServicesModel
 *
 * @author nczirjak
 */
class DisseminationServicesModel extends ArcheModel {
    
    private $repodb;
    private $sqlResult = array();
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }
  
    public function getViewData(string $identifier = "", string $dissemination = '' ): array {
        
        switch ($dissemination) {
            case "collection":
                $this->getCollectionData($identifier);
                break;
            default:
                break;
        }
        return $this->sqlResult;
    }
    
    private function getCollectionData(string $identifier) {
        try {
            $query = $this->repodb->query("select * from collection_views_func('".$identifier."');");
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_ASSOC);
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            $this->sqlResult = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            $this->sqlResult = array();
        }
    }
        
}