<?php

namespace Drupal\acdh_repo_gui\Model;

/**
 * Description of OntologyTwigExtensionModel
 *
 * @author nczirjak
 */
class OntologyTwigExtensionModel extends ArcheModel
{
    protected $repodb;
    protected $siteLang;
    private $dbResult = array();

    public function __construct()
    {
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
    }

    public function getViewData(): array
    {
        return $this->getImportDate();
    }

    /**
     * Get the latest owl file import from the DB
     * @return array
     */
    private function getImportDate(): array
    {
        try {
            $this->setSqlTimeout();
            $query = $this->repodb->query(
                "select i.id, 
                (select mv.value from metadata_view as mv where mv.id = i.id and mv.property = :avdate) 
                from identifiers as i 
                where 
                i.ids = 'https://vocabs.acdh.oeaw.ac.at/schema' limit 1
                ",
                array(
                        ':avdate' => $this->repo->getSchema()->creationDate
                    )
            );
            $this->dbResult = $query->fetchAssoc();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        $this->changeBackDBConnection();
        return $this->dbResult;
    }
}
