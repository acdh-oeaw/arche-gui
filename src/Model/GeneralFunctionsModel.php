<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class GeneralFunctionsModel extends ArcheModel
{
    protected $repodb;
    private $identifier;
    private $sqlResult = array();
    
    
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
    public function getViewData(string $identifier = ''): array
    {
        if (empty($identifier)) {
            return array();
        }
        $this->identifier = $identifier;
        return $this->getRepoIdBySpecialID();
    }
    
    /**
     * Get the ARCHE REPO ID based on the special identifier
     *
     * @return array
     */
    private function getRepoIdBySpecialID(): array
    {
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select id from identifiers where ids LIKE :id limit 1;",
                array(
                    ':id' => '%'.$this->identifier.'%'
                )
            );
            
            $this->sqlResult = $query->fetchAll();
            $this->changeBackDBConnection();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
        return $this->sqlResult;
    }
}
