<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class BlocksModel extends ArcheModel {
    
    private $repodb;
    
    
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
    public function getViewData(string $identifier = "entity"): array {
        switch ($identifier) {
            case "entity":
                return $this->getEntityData();
                break;
            case "years":
                return $this->getYearsData();
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
    private function getEntityData(): array {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query("
                select count(value), value
                from metadata 
                where property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
                and value LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%'
                group by value
                order by value asc"
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
    
    /**
     * Generate the year box data
     * 
     * @return array
     */
    private function getYearsData(): array {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query("
                select
                    count(EXTRACT(YEAR FROM to_date(value,'YYYY'))), 
                    EXTRACT(YEAR FROM to_date(value,'YYYY')) as year
                from metadata 
                where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'
                group by year
                order by year desc"
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