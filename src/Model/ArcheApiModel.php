<?php

namespace Drupal\acdh_repo_gui\Model;

include 'ArcheModel.php';
use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class ArcheApiModel extends ArcheModel
{
    protected $repodb;
    private $properties;
    
    public function __construct()
    {
        parent::__construct();
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
            case 'getMembers':
                return $this->getMembers();
                break;
            case 'getRPR':
                return $this->getRPR();
                break;
            case 'rootTable':
                return $this->getRootTableOntology();
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
            $this->setSqlTimeout('10000');
            $query = $this->repodb->query(
                "SELECT * from gui.apiGetData(:type, :searchStr)",
                array(
                    ':type' => $this->properties->type,
                    ':searchStr' => $this->properties->searchStr
                ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            
            $result = $query->fetchAll(\PDO::FETCH_CLASS|\PDO::FETCH_GROUP);
        } catch (\Exception $ex) {
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
    * Generate the Members data for the root repoid
    *
    * @return array
    */
    private function getMembers(): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout('10000');
            $query = $this->repodb->query(
                "SELECT * from gui.get_members_func(:repoid, :lang)",
                array(':repoid' => $this->properties->repoid,
                        ':lang' => $this->properties->lang)
            );
            
            $result = $query->fetchAll();
        } catch (\Exception $ex) {
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
            $this->setSqlTimeout('10000');
            $query = $this->repodb->query(
                "SELECT * from gui.count_binaries_collection_func();"
            );
            $result = $query->fetchAll(\PDO::FETCH_CLASS);
        } catch (\Exception $ex) {
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
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            'skipNamespace'     => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
            'ontologyNamespace' => 'https://vocabs.acdh.oeaw.ac.at/schema#',
            'parent'            => 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf',
            'label'             => 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
            'order'             => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            'cardinality'       => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
            'recommended'       => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
            'langTag'           => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
            'vocabs'            => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
            'altLabel'          => 'http://www.w3.org/2004/02/skos/core#altLabel'
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
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            //'skipNamespace'     => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
            'ontologyNamespace' => 'https://vocabs.acdh.oeaw.ac.at/schema#',
            'parent'            => 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf',
            'label'             => 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
            //'order'             => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            //'cardinality'       => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
            //'recommended'       => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
            //'langTag'           => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
            //'vocabs'            => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
            //'altLabel'          => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];
       
        $ontology = new \acdhOeaw\arche\Ontology($conn, $cfg);
        
        $collectionProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Collection')->properties;
        $projectProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Project')->properties;
        $resourceProp = $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Resource')->properties;
        
        return array('collection' => $collectionProp, 'project' => $projectProp, 'resource' => $resourceProp);
    }
    
    /**
     * Get ontology for the roottable
     * @return array
     */
    private function getRootTableOntology(): array
    {
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            'skipNamespace'     => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
            'ontologyNamespace' => 'https://vocabs.acdh.oeaw.ac.at/schema#',
            'parent'            => 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf',
            'label'             => 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
            'order'             => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            'cardinality'       => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
            'recommended'       => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
            'langTag'           => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
            'vocabs'            => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
            'label'             => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];
        $ontology = new \acdhOeaw\arche\Ontology($conn, $cfg);
        
        //check the properties
        $project = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Project')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Project')->properties : "" ;
        
        $collection = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Collection')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Collection')->properties : "" ;
                
        $resource = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Resource')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Resource')->properties : "" ;
        
        $metadata = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Metadata')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Metadata')->properties : "" ;
                
                
        $image = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Image')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Image')->properties : "" ;
        
        $publication = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Publication')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Publication')->properties : "" ;
        
        $place = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Place')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Place')->properties : "" ;
        
        
        $organisation = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Organisation')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Organisation')->properties : "" ;
        
        $person = (isset($ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Person')->properties))?
            $ontology->getClass('https://vocabs.acdh.oeaw.ac.at/schema#Person')->properties : "" ;
        
        return array(
            'project' => $project, 'collection' => $collection,
            'resource' => $resource, 'metadata' => $metadata,
            'image' => $image, 'publication' => $publication,
            'place' => $place, 'organisation' => $organisation,
            'person' => $person
        );
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
            $this->setSqlTimeout('10000');
            $query = $this->repodb->query(
                "SELECT * from gui.inverse_data_func(:repoid);",
                array(
                    ':repoid' => $this->properties->repoid
                )
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
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select 
                        DISTINCT(i.id),  mv.property, mv.value, mv.lang
                    from identifiers as i
                    left join metadata_view as mv on mv.id = i.id
                    where i.id = :repoid
                        and property in (
                                'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', 
                                'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate', 
                                'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' 
                        );",
                array(
                    ':repoid' => $this->properties->repoid
                )
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
            $this->setSqlTimeout();
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
    
    /**
     * Check the repoid in the DB
     * @return array
     */
    private function getRPR(): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select * from gui.related_publications_resources_views_func(:repoid, :lang)",
                array(
                    ':repoid' => $this->properties->repoid,
                    ':lang' => $this->properties->lang
                )
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
