<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of ApiModel
 *
 * @author nczirjak
 */
class ChildApiModel extends ArcheModel
{
    private $repodb;
    private $result = array();
    private $data = array();
    private $childProperties = array();
    private $rootAcdhType;
    private $sqlTypes;
    private $siteLang = 'en';
    
    public function __construct()
    {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    public function getAcdhtype(): string
    {
        if (!empty($this->rootAcdhType)) {
            return $this->rootAcdhType;
        }
        return '';
    }
    /**
     * Get the actual page view data
     *
     * @param string $identifier
     * @param int $limit
     * @param int $page
     * @param string $orderby
     * @return array
     */
    public function getViewData(string $identifier = "", int $limit = 10, int $page = 0, string $orderby = "titleasc"): array
    {
        $order = $this->ordering($orderby);
        $prop = $order->property;
        $ord = $order->order;
        
        $queryStr = "select * from gui.child_views_func('".$identifier."', '".$limit."',  "
                    . " '".$page."', '".$ord."', '".$prop."', '".$this->siteLang."' ";
            
        if (!empty($this->sqlTypes)) {
            $queryStr .= ", ".$this->sqlTypes." ";
        } else {
            $queryStr .= ", ARRAY[]::text[] ";
        }
        $queryStr .= " );";
            
        //get the requested sorting
        try {
            $query = $this->repodb->query($queryStr);
            
            $this->data = $query->fetchAll();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->data = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->data = array();
        }
        
        $this->changeBackDBConnection();
        return $this->data;
    }
    
    /**
     * Create the order values for the sql
     *
     * @param string $orderby
     * @return object
     */
    private function ordering(string $orderby = "titleasc"): object
    {
        $result = new \stdClass();
        $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
        $result->order = 'asc';
        
        if ($orderby == "titleasc") {
            $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
            $result->order = 'asc';
        } elseif ($orderby == "titledesc") {
            $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
            $result->order = 'desc';
        } elseif ($orderby == "dateasc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'asc';
        } elseif ($orderby == "datedesc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'desc';
        }
        return $result;
    }
    
    /**
     * Get the number of the child resources for the pagination
     *
     * @param string $identifier
     */
    public function getCount(string $identifier): int
    {
        try {
            $queryStr = "select * from gui.child_sum_views_func('".$identifier."'";
            
            if (!empty($this->sqlTypes)) {
                $queryStr .= ", ".$this->sqlTypes." ";
            } else {
                $queryStr .= ", ARRAY[]::text[] ";
            }
            $queryStr .= " );";
        
            $query = $this->repodb->query($queryStr);
            $result = $query->fetch();
          
            $this->changeBackDBConnection();
            if (isset($result->countid)) {
                return (int)$result->countid;
            }
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
        $this->changeBackDBConnection();
        return 0;
    }
   
    /**
     * Get the root resource acdh type
     *
     * @param string $repoid
     * @return string
     */
    private function getProperties(string $repoid): string
    {
        try {
            $query = $this->repodb->query(
                "select value from metadata_view where id = :id and property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type' and value like '%/vocabs.acdh.oeaw.ac.at/schema#%' limit 1",
                array(':id' => $repoid)
            );
            
            $result = $query->fetch();
            if (isset($result->value)) {
                return $result->value;
            }
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        
        $this->changeBackDBConnection();
        return '';
    }
    
    /**
     * Get the properties for the sql search by the root resource acdh type
     * @param string $class
     * @return array
     */
    public function getPropertiesByClass(string $repoid)
    {
        $class = $this->getProperties($repoid);
        $property = '';
        $this->rootAcdhType = $class;
        if (!empty($class)) {
            $class = strtolower(str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $class));
        }
        
        switch ($class) {
            case 'organisation':
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasContributor', 'https://vocabs.acdh.oeaw.ac.at/schema#hasFunder',
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasOwner', 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicensor',
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasRightsHolder'
                );
                break;
            case 'publication':
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasDerivedPublication', 'https://vocabs.acdh.oeaw.ac.at/schema#hasSource',
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasReferencedBy'
                );
                break;
            case 'person':
                $this->childProperties = array(
                    'http://www.w3.org/2004/02/skos/core#narrower'
                );
                break;
            case 'project':
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasRelatedProject'
                );
                break;
            case 'institute':
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasMember'
                );
                break;
            case 'place':
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#hasSpatialCoverage'
                );
                break;
            default:
                $this->childProperties = array(
                    'https://vocabs.acdh.oeaw.ac.at/schema#isPartOf'
                );
            break;
        }
        //create the sql string array for the query
        $this->formatTypeFilter();
    }
    
    /**
     * Format the acdh type for the sql query as an array
     * @return string
     */
    private function formatTypeFilter()
    {
        $this->sqlTypes = "";
        if (isset($this->childProperties)) {
            $count = count($this->childProperties);
            if ($count > 0) {
                $this->sqlTypes .= 'ARRAY [ ';
                $i = 0;
                foreach ($this->childProperties as $t) {
                    $this->sqlTypes .= "'$t'";
                    if ($count - 1 != $i) {
                        $this->sqlTypes .= ', ';
                    } else {
                        $this->sqlTypes .= ' ]';
                    }
                    $i++;
                }
            } else {
                $this->sqlTypes = "";
            }
        }
    }
}
