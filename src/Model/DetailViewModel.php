<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class DetailViewModel extends ArcheModel
{
    protected $repodb;
    protected $siteLang;

    public function __construct()
    {
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
    }

    /**
     * Get the detail view data from DB
     *
     * @param string $identifier
     * @return array
     */
    public function getViewData(string $identifier = ""): array
    {
        if (empty($identifier)) {
            return array();
        }
        $result = array();
        try {
            $this->setSqlTimeout();
            //run the actual query
            $query = $this->repodb->query(" select * from gui.detail_view_func(:id, :lang) order by property, lastname", array(':id' => $identifier, ':lang' => $this->siteLang));
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

    public function getViewDataLib(string $identifier = ""): object
    {
        if (empty($identifier)) {
            return array();
        }
        
        try {
            $res = new \acdhOeaw\arche\lib\RepoResource($identifier, $this->repo);
            $res->loadMetadata(true, \acdhOeaw\arche\lib\RepoResource::META_RELATIVES);
            return $res->getGraph();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return new \stdClass();
        }
    }

    /**
     * Get the breadcrumb data for the detail view
     *
     * @param string $identifier
     * @return array
     */
    public function getBreadCrumbData(string $identifier = ''): array
    {
        if (empty($identifier)) {
            return array();
        }

        $result = [];
        try {
            $this->setSqlTimeout();
            //run the actual query
            $query = $this->repodb->query(" select * from gui.breadcrumb_view_func(:id, :lang) order by depth desc ", array(':id' => $identifier, ':lang' => $this->siteLang));
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
     * Get the ontology for the tooltip
     * @return array
     */
    public function getTooltipOntology(): array
    {
        $result = array();

        try {
            $this->setSqlTimeout();
            //run the actual query
            $query = $this->repodb->query(" select * from gui.ontology_func(:lang) ", array(':lang' => $this->siteLang));
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
}
