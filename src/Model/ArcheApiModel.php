<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class ArcheApiModel extends ArcheModel
{
    private $repodb;
    private $properties;
    
    public function __construct()
    {
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
    public function getViewData(string $identifier = "metadata", object $properties = null): array
    {
        $this->properties = $properties;
        
        switch ($identifier) {
            case 'metadata':
                return $this->getOntology();
                break;
             case 'metadataGui':
                return $this->getOntologyGui();
                break;
            case 'inverse':
                return $this->getInverseData();
                break;
            case 'checkIdentifier':
                return $this->checkIdentifier();
                break;
            case 'gndPerson':
                return $this->getGNDPersonData();
                break;
            case 'countCollsBins':
                return $this->countCollectionsBinaries();
                break;
            default:
                return $this->getData();
                break;
        }
    }
    
    /**
     * Generate the entity box data
     *
     * @return array
     */
    private function getData(): array
    {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query(
                "SELECT * from gui.apiGetData('".$this->properties->type."', '".$this->properties->searchStr."');"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_GROUP);
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        }
        
        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * Count the main collections and binary files for the ckeditor plugin api endpoint
     *
     * @return array
     */
    private function countCollectionsBinaries(): array
    {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query(
                "SELECT * from gui.count_binaries_collection_func();"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS);
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        }
        
        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * get the onotology data based on the acdh type
     * @return array
     */
    private function getOntology(): array
    {
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
    
    /**
     * get the onotology data for the js plugin
     * @return array
     */
    private function getOntologyGui(): array
    {
        $dbconnStr = yaml_parse_file(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            'skipNamespace' => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
            'order'         => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            'cardinality'   => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
            'recommended'   => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
            'altLabel'      => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];
        $ontology = new \acdhOeaw\arche\Ontology($conn, $cfg);
        
        $collectionProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Collection')->properties;
        $projectProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Project')->properties;
        $resourceProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Resource')->properties;
        
        return array('collection' => $collectionProp, 'project' => $projectProp, 'resource' => $resourceProp);
    }
    
    /**
     * get the resource inverse data
     * Inverse is where the value is not identifier, pid or ispartof
     * @return array
     */
    private function getInverseData(): array
    {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query(
                "SELECT * from gui.inverse_data_func('".$this->properties->repoid."');"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_GROUP);
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        }
        
        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * Check the repoid in the DB
     * @return array
     */
    private function checkIdentifier(): array
    {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query(
                "select 
                        DISTINCT(i.id),  mv.property, mv.value, mv.lang
                    from identifiers as i
                    left join metadata_view as mv on mv.id = i.id
                    where i.id = ".$this->properties->repoid."
                        and property in (
                                'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', 
                                'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate', 
                                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                        );"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS);
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        }
        $this->changeBackDBConnection();
        return $result;
    }
    
    
    /**
     * Generate GND person data
     *
     * @return array
     */
    private function getGNDPersonData(): array
    {
        $result = array();
        //run the actual query
        try {
            $query = $this->repodb->query(
                "select 
			DISTINCT(mv.id) as repoid, i.ids as gnd 
                    from metadata_view as mv
                    left join identifiers as i on mv.id = i.id 
                    where
                        mv.property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and mv.value = 'https://vocabs.acdh.oeaw.ac.at/schema#Person'
                        and i.ids like '%gnd%';"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS);
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $result = array();
        }
        $this->changeBackDBConnection();
        return $result;
    }
}
