<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class BlocksModel extends ArcheModel
{
    protected $repodb;
    protected $siteLang;

    public function __construct()
    {
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
    }

    /**
     * Get the data for the left side boxes
     *
     * @param string $identifier
     * @return array
     */
    public function getViewData(string $identifier = "entity", array $params = array()): array
    {
        switch ($identifier) {
            case "entity":
                return $this->getEntityData();
                break;
            case "years":
                return $this->getYearsData();
                break;
            case "versions":
                return $this->getVersionsData($params);
                break;
            case "category":
                return $this->getCategoryData();
                break;
            case "lastModify":
                return $this->lastModificationDate();
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
    private function getEntityData(): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "
                select count(value), value
                from metadata 
                where property = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#type'
                and value LIKE 'https://vocabs.acdh.oeaw.ac.at/schema#%'
                group by value
                order by value asc"
            );
            $result = $query->fetchAll();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }

        $this->changeBackDBConnection();
        return $result;
    }

    /**
     * Generate the year box data
     *
     * @return array
     */
    private function getYearsData(): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "
                select
                    count(EXTRACT(YEAR FROM to_date(value,'YYYY'))), 
                    EXTRACT(YEAR FROM to_date(value,'YYYY')) as year
                from metadata 
                where property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate'
                group by year
                order by year desc"
            );
            $result = $query->fetchAll();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }

        $this->changeBackDBConnection();
        return $result;
    }
    
    /**
     * Get the newest version id, to we can fetch the whole tree
     * @param string $id
     * @return string
     */
    private function getMainVersionResourceID(string $id): string
    {
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "WITH RECURSIVE parent_subordinates AS (
                    SELECT
                        mv.id,
                        -1 as depthval
                    FROM
                        metadata_view as mv
                    WHERE
                        mv.value = :id
                        and mv.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
                    UNION
                        SELECT
                            mv2.id,
                            depthval - 1
                        FROM
                            metadata_view mv2
                        INNER JOIN parent_subordinates s ON s.id = CAST(mv2.value as bigint) and mv2.property = 'https://vocabs.acdh.oeaw.ac.at/schema#isNewVersionOf'
                ) select id from parent_subordinates order by depthval asc limit 1 ",
                array(':id' => $id)
            );
            $result = $query->fetch();
           
            if (isset($result->id)) {
                return $result->id;
            }
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        $this->changeBackDBConnection();
        return $id;
    }

    /**
     * Get the Versions block data
     * @param array $params
     * @return array
     */
    public function getVersionsData(array $params): array
    {
        $result = array();
        //get the main id
        $id = $this->getMainVersionResourceID($params['identifier']);
       
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select * from gui.getResourceVersion(:id, :lang) order by depth",
                array(':id' => $id, ':lang' => $params['lang'])
            );
            $result = $query->fetchAll();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        $this->changeBackDBConnection();
        return $result;
    }

    private function getCategoryData(): array
    {
        $result = array();
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select count(mv.value),  mv2.value, mv2.id
                from metadata_view as mv
                left join metadata_view as mv2 on mv2.id = CAST(mv.value as int)
                where mv.property = :category
                and mv2.property = :title
                and mv2.lang = :lang
                group by mv2.value, mv2.id
                order by mv2.value asc",
                array(
                    ':category' => $this->repo->getSchema()->__get('namespaces')->ontology.'hasCategory',
                    ':title' => $this->repo->getSchema()->__get('label'),
                    ':lang' => $this->siteLang
                    )
            );
            $result = $query->fetchAll();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }

        $this->changeBackDBConnection();
        return $result;
    }

    /**
     * Get the DB last modification date for the cache
     * @return array
     */
    public function lastModificationDate(): object
    {
        $result = new \stdClass();
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select max(value_t) from metadata where property  = :prop",
                array(
                    ':prop' => $this->repo->getSchema()->__get('modificationDate')
                    )
            );
            $result = $query->fetch();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        $this->changeBackDBConnection();
        return $result;
    }
}
