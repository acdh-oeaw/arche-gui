<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class ArcheApiModel extends ArcheModel {
    
    private $repodb;
    private $properties;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }
    
    /**
     * Get the data for the left side boxes
     * 
     * @param string $identifier
     * @return array
     */
    public function getViewData(string $identifier = "getData", object $properties = null): array {
        $this->properties = $properties;
                
        switch ($identifier) {
            case "getData":
                return $this->getData();
                break;
            case "Persons":
                return $this->getData();
                break;
            default:
                return array();
                break;
        }
    }
    
    /**
     * Generate the entity box data
     * 
     * @return array
     */
    private function getData(): array {
        $result = array();
        //run the actual query
        
        try {
            $query = $this->repodb->query("SELECT * from gui.apiGetData('".$this->properties->type."', '".$this->properties->searchStr."') 
                ;"  
            );
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