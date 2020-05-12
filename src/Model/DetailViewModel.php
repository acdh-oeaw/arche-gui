<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
/**
 * Description of DetailViewModel
 *
 * @author nczirjak
 */
class DetailViewModel extends ArcheModel {
    
    private $repodb;
    private $siteLang;
    private $ontology;
    private $vocabsFile;
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->vocabsFile = \Drupal::service('file_system')->realpath(file_default_scheme() . "://")."/vocabsCache.json";
        if (!file_exists($this->vocabsFile)) {
            $file = fopen($this->vocabsFile, "w");
            fclose($file);
            chmod($this->vocabsFile, 0777);
        }
    }
    
    /**
     * Get the detail view data from DB
     * 
     * @param string $identifier
     * @return array
     */
    public function getViewData(string $identifier = ""): array {
        if(empty($identifier)) { return array();}
        $result = array();
        try {
            //run the actual query
            $query = $this->repodb->query(" select * from gui.detail_view_func(:id) ", array(':id' => $identifier));
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
     * Get the breadcrumb data for the detail view 
     * 
     * @param string $identifier
     * @return array
     */
    public function getBreadCrumbData(string $identifier = ''): array {
        if(empty($identifier)) { return array();}
        
        $result = array();
        try {
            //run the actual query
            $query = $this->repodb->query(" select * from gui.breadcrumb_view_func(:id) order by depth desc ", array(':id' => $identifier));
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
     * Get the ontology for the tooltip
     * @return array
     */
    public function getTooltipOntology(): array {
        $result = array();
        
        try {
            //run the actual query
            $query = $this->repodb->query(" select * from gui.ontology_func(:lang) ", array(':lang' => $this->siteLang));
            $result = $query->fetchAll(\PDO::FETCH_CLASS);
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
    
    public function getVocabsCacheData(string $baseURL): array {
        
        $dbconnStr = yaml_parse_file(drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml')['dbConnStr']['guest'];
        
        $conn = new \PDO($dbconnStr);
        $cfg = (object) [
            'skipNamespace' =>  $baseURL.'%', // don't forget the '%' at the end!
            'order'         => 'https://vocabs.acdh.oeaw.ac.at/schema#ordering',
            'recommended'   => 'https://vocabs.acdh.oeaw.ac.at/schema#recommendedClass',
            'langTag'       => 'https://vocabs.acdh.oeaw.ac.at/schema#langTag',
            'vocabs'        => 'https://vocabs.acdh.oeaw.ac.at/schema#vocabs',
        ];
        
        try{
            $this->ontology = new \acdhOeaw\arche\Ontology($conn, $cfg);
            $this->ontology->fetchVocabularies($this->vocabsFile, 'PT0S');
            
            
            //$license = $this->ontology->getProperty(null, 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense');
            //$this->ontology->fetchVocabularies($this->vocabsFile, 'P1000D');
            //$property = $this->ontology->getProperty(null, 'https://vocabs.acdh.oeaw.ac.at/schema#hasLicense');
            //print_r($property);
            //echo $property->vocabsValues['https://vocabs.acdh.oeaw.ac.at/archelicenses/cc-by-4-0']->getLabel('en');
            // echo "<pre>";
            //var_dump($property->vocabsValues['https://vocabs.acdh.oeaw.ac.at/archelicenses/cc-by-4-0']->getLabel('de'));
            //echo "</pre>";
            return array();
            
        } catch (Exception $ex) {
            echo 'simple exception';
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        } catch (\EasyRdf\Exception $ex){
            echo 'easyrdf exception';
            echo "<pre>";
            var_dump($ex->getMessage());
            echo "</pre>";
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return array();
        }
    }
        
}