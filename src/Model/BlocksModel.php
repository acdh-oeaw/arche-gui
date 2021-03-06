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
     * Get the Versions block data
     * @param array $params
     * @return array
     */
    private function getVersionsData(array $params): array
    {
        $result = array();
        //run the actual query
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select * from gui.getResourceVersion(:id, :lang) ",
                array(':id' => $params['identifier'], ':lang' => $params['lang'])
            );
            $result = $query->fetchAll();
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
