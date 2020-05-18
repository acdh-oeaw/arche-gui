<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of RootModel
 *
 * @author nczirjak
 */
class RootViewModel extends ArcheModel
{
    private $repodb;
    private $sqlResult;
    private $siteLang = 'en';
    /* ordering */
    private $limit;
    private $offset;
    private $order;
    /* ordering */
    
    
    public function __construct()
    {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
    }
    
    private function initPaging(int $limit, int $page, string $order)
    {
        $this->limit = $limit;
        ($page == 0 || $page == 1) ? $this->offset = 0 : $this->offset = $limit * ($page -1);
        
        switch ($order) {
            case 'dateasc':
                $this->order = "avdate asc";
                break;
            case 'datedesc':
                $this->order = "avdate desc";
                break;
            case 'titleasc':
                $this->order = "title asc";
                break;
            case 'titledesc':
                $this->order = "title desc";
                break;
            default:
                $this->order = "avdate desc";
        }
    }
        
    /**
     * get the root views data
     *
     * @return array
     */
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc"): array
    {
        $this->initPaging($limit, $page, $order);
        
        try {
            $query = $this->repodb->query("
                SELECT 
                *
                from gui.root_views_func('".$this->siteLang."') 
                where title is not null 
                order by ".$this->order." limit ".$this->limit." offset ".$this->offset.";");
            
            $this->sqlResult = $query->fetchAll();
            $this->changeBackDBConnection();
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
        return $this->sqlResult;
    }
    
    /**
     * Count the actual root resources
     * @return int
     */
    public function countRoots(): int
    {
        $result = array();
        try {
            $query = $this->repodb->query("select id from gui.count_root_views_func();  ");
            $this->sqlResult = $query->fetch();
            $this->changeBackDBConnection();
            if (isset($this->sqlResult->id)) {
                return (int)$this->sqlResult->id;
            }
        } catch (Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
        return 0;
    }
}
