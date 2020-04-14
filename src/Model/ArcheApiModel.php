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
    public function getViewData(string $identifier = "metadata", object $properties = null): array {
        $this->properties = $properties;
        if($identifier == 'metadata') {
            return $this->getOntology();
        } 
        return $this->getData();
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
            $query = $this->repodb->query(
                    "SELECT * from gui.apiGetData('".$this->properties->type."', '".$this->properties->searchStr."');" 
                    );
            $result = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_GROUP);
        } catch (Exception $ex) {
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
             $result = array();
        }
        
        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * get the onotology data based on the acdh type
     * @return array
     */
    private function getOntology(): array {
        $dbconnStr = yaml_parse_file(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            'skipNamespace' => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
            'order'         => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            'recommended'   => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
        ];
        $ontology = new \acdhOeaw\arche\Ontology($conn, $cfg);
        return (array)$ontology->getClass($this->properties->type);
    }
}