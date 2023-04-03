<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DisseminationServicesModel
 *
 * @author nczirjak
 */
class DisseminationServicesModel extends ArcheModel
{
    protected $repoDb;
    protected $drupalDb;
    private $sqlResult = array();
    
    public function __construct()
    {
        parent::__construct();
    }
  
    public function getViewData(string $identifier = "", string $dissemination = ''): array
    {
        switch ($dissemination) {
            case "collection":
                $this->getCollectionData($identifier);
                break;
            default:
                break;
        }
        return $this->sqlResult;
    }
    
    private function getCollectionData(string $identifier)
    {
        try {
            $this->setSqlTimeout('60000');
            $query = $this->drupalDb->query(
                "select * from gui.collection_views_func(:id);",
                array(
                       'id' => $identifier
                    )
            );
            $this->sqlResult = $query->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->sqlResult = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->sqlResult = array();
        }
        $this->closeDBConnection();
    }
}
