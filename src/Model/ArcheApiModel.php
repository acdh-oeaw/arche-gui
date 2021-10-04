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
        $this->setUpProperties($properties);

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
            case 'gndPerson':
                return $this->getGNDPersonData();
                break;
            case 'getMembers':
                return $this->getMembers();
                break;
            case 'getRPR':
                return $this->getRPR();
                break;
            case 'getRPRAjax':
                return $this->getRPRAjax();
                break;
            case 'rootTable':
                return $this->getRootTableOntology();
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
        return array();
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
     * get the onotology data based on the acdh type
     * @return array
     */
    private function getOntology(): array
    {
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
                    'skipNamespace' => $this->properties->baseUrl . '%', // don't forget the '%' at the end!
                    'ontologyNamespace' => 'https://vocabs.acdh.oeaw.ac.at/schema#',
                    'parent' => 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf',
                    'label' => 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
                    'order' => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
                    'cardinality' => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
                    'recommended' => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
                    'langTag' => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
                    'vocabs' => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
                    'altLabel' => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];
        $ontology = new \acdhOeaw\arche\lib\schema\Ontology($conn, $cfg);
        return (array) $ontology->getClass($this->properties->type);
    }

    /**
     * get the onotology data for the js plugin
     * @return array
     */
    private function getOntologyGui(): array
    {
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml')['dbConnStr']['guest'];
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
                    //'skipNamespace'     => $this->properties->baseUrl.'%', // don't forget the '%' at the end!
                    'ontologyNamespace' => 'https://vocabs.acdh.oeaw.ac.at/schema#',
                    'parent' => 'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf',
                    'label' => 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle',
                //'order'             => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
                //'cardinality'       => 'https://vocabs.acdh.oeaw.ac.at/schema#cardinality',
                //'recommended'       => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
                //'langTag'           => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
                //'vocabs'            => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
                //'altLabel'          => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];

        $ontology = new \acdhOeaw\arche\lib\schema\Ontology($conn, $cfg);

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
        $dbconnStr = yaml_parse_file(\Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml')['dbConnStr']['guest'];
      
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
                    'skipNamespace' => $this->properties->baseUrl . '%', // don't forget the '%' at the end!
                    'ontologyNamespace' => $this->repo->getSchema()->namespaces->ontology,
                    'parent' => $this->repo->getSchema()->namespaces->ontology . 'isPartOf',
                    'label' => $this->repo->getSchema()->namespaces->ontology . 'hasTitle',
                    'order' => $this->repo->getSchema()->namespaces->ontology . 'ordering',
                    'cardinality' => $this->repo->getSchema()->namespaces->ontology . 'cardinality',
                    'recommended' => $this->repo->getSchema()->namespaces->ontology . 'recommendedClass',
                    'langTag' => $this->repo->getSchema()->namespaces->ontology . 'langTag',
                    'vocabs' => $this->repo->getSchema()->namespaces->ontology . 'vocabs',
                    'label' => 'http://www.w3.org/2004/02/skos/core#altLabel'
        ];

        $ontology = new \acdhOeaw\arche\lib\schema\Ontology($conn, $cfg);

        //check the properties
        $project = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Project')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Project')->properties : "";

        $collection = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Collection')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Collection')->properties : "";

        $topCollection = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'TopCollection')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'TopCollection')->properties : "";

        $resource = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Resource')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Resource')->properties : "";

        $metadata = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Metadata')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Metadata')->properties : "";


        $image = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Image')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Image')->properties : "";

        $publication = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Publication')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Publication')->properties : "";

        $place = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Place')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Place')->properties : "";


        $organisation = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Organisation')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Organisation')->properties : "";

        $person = (isset($ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Person')->properties)) ?
                $ontology->getClass($this->repo->getSchema()->namespaces->ontology . 'Person')->properties : "";

        return array(
            'project' => $project,
            'topcollection' => $topCollection, 'collection' => $collection,
            'resource' => $resource, 'metadata' => $metadata,
            'publication' => $publication,
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
            $result = $query->fetchAll(\PDO::FETCH_CLASS | \PDO::FETCH_GROUP);
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
     * Related Publications and resources table data
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
     * Related Publications and resources table data - Ajax version endpoint
     * @return array
     */
    private function getRPRAjax(): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select * from gui.related_publications_resources_views_func(:repoid, :lang)"
                    . " ORDER BY " . $this->properties->property . " " . $this->properties->order . " LIMIT :limit OFFSET :page ",
                array(
                        ':repoid' => $this->properties->repoid,
                        ':lang' => $this->properties->lang,
                        ':limit' => $this->properties->limit,
                        ':page' => $this->properties->page
                    )
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
     * Setup the SQL properties
     * @param type $properties
     * @return void
     */
    private function setUpProperties($properties): void
    {
        $this->properties = $properties;
        if (isset($this->properties->fieldOrder)) {
            $obj = $this->orderingByFields($this->properties->fields, $this->properties->order);
            $this->properties->order = $obj->order;
            $this->properties->property = $obj->property;
        } elseif (isset($this->properties->order)) {
            $obj = $this->ordering($this->properties->order);
            $this->properties->order = $obj->order;
            $this->properties->property = $obj->property;
        }
    }
}
