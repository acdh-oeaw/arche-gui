<?php

namespace Drupal\acdh_repo_gui\Model;

/**
 * Description of OntologyTwigExtensionModel
 *
 * @author nczirjak
 */
class OntologyTwigExtensionModel extends ArcheModel
{
    protected $repoDb;
    protected $drupalDb;
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
            $query = $this->drupalDb->query(
                "select i.id, 
                (select mv.value from metadata_view as mv where mv.id = i.id and mv.property = :avdate),
                (select mv2.value as version from metadata_view as mv2 where mv2.id = i.id and mv2.property = :version) 
                from identifiers as i 
                where 
                i.ids = 'https://vocabs.acdh.oeaw.ac.at/schema' limit 1
                ",
                array(
                        ':avdate' => $this->repoDb->getSchema()->creationDate,
                        ':version' => $this->repoDb->getSchema()->ontology->version
                    )
            );
            $this->dbResult = $query->fetchAssoc();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
        $this->closeDBConnection();
        return $this->dbResult;
    }
}
