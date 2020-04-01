<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;
use acdhOeaw\acdhRepoLib\SearchConfig;
use acdhOeaw\acdhRepoLib\RepoResourceInterface;
use acdhOeaw\acdhRepoLib\SearchTerm;
/**
 * Description of SearchViewModel
 *
 * @author nczirjak
 */
class SearchViewModel extends ArcheModel {
    
    private $repodb;
    private $repolibDB;
    private $sqlResult;
    private $siteLang = 'en';
    private $searchCfg;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->searchCfg = new \acdhOeaw\acdhRepoLib\SearchConfig();
        $this->repolibDB = \acdhOeaw\acdhRepoLib\RepoDb::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml', 'guest');
    }
    
     /**
     * get the search view data
     * 
     * @return array
     */
    public function getViewData(int $limit = 10, int $page = 0, string $order = "datedesc", string $metavalue = ''): array {
        
        //helper function to create object from the metavalue string
        
        $this->searchCfg->metadataMode = RepoResourceInterface::META_RESOURCE; 
        $this->searchCfg->ftsQuery             = "Wollmilchsau";
        //$repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml', 'guest');
        //$repodb->setQueryLog($log);
        
        $resTitle = $this->repolibDB->getPdoStatementBySearchTerms(
                [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasTitle', "Wollmilchsau", '@@')], 
                $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');
        $resDesc = $this->repolibDB->getPdoStatementBySearchTerms(
                [new SearchTerm('https://vocabs.acdh.oeaw.ac.at/schema#hasDescription', "Wollmilchsau", '@@')], 
                $this->searchCfg)->fetchAll(\PDO::FETCH_CLASS, 'ArrayObject');

        $results = array_merge($resTitle, $resDesc);
        
        foreach ($results as $res) {
            
            if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle') {
                $this->sqlResult[] = $res;
            }
             if($res->property  == 'https://vocabs.acdh.oeaw.ac.at/schema#hasDescription') {
                $this->sqlResult[] = $res;
            }
        }
        
        return $this->sqlResult;
    }
}
