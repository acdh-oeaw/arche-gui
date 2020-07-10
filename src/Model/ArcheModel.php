<?php

namespace Drupal\acdh_repo_gui\Model;

use acdhOeaw\acdhRepoLib\Repo;

/**
 * Description of ArcheModel
 *
 * @author nczirjak
 */
abstract class ArcheModel
{
    private $repodb;
    
    public function __construct()
    {
        //set up the DB connections
        $this->setActiveConnection();
    }
    
    /**
     * Allow the DB connection
     */
    private function setActiveConnection() 
    {
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }
    
    public function changeBackDBConnection()
    {
        \Drupal\Core\Database\Database::setActiveConnection();
    }
    
    /**
     * Set the sql execution max time
     * @param string $timeout
     */
    public function setSqlTimeout(string $timeout = '7000') 
    {
        $this->setActiveConnection();
        
        try {
            $this->repodb->query(
                "SET statement_timeout TO :timeout;", array(':timeout' => $timeout)
            )->fetch();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
    }
    
    /**
     * get the views data
     *
     * @return array
     */
    abstract public function getViewData(): array;
}
