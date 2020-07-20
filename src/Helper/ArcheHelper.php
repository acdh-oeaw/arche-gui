<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;

/**
 * Description of ArcheHelper
 *
 * @author nczirjak
 */
abstract class ArcheHelper
{
    protected $generalFunctions;
    protected $config;
    protected $repo;
    private $siteLang;
    /*
     * We will store the custom translations config inside this variable
    */
    public $langConf;
    
    public static $prefixesToChange = array(
        "http://fedora.info/definitions/v4/repository#" => "fedora",
        "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#" => "ebucore",
        "http://www.loc.gov/premis/rdf/v1#" => "premis",
        "http://www.jcp.org/jcr/nt/1.0#" => "nt",
        "http://www.w3.org/2000/01/rdf-schema#" => "rdfs",
        "http://www.w3.org/ns/ldp#" => "ldp",
        "http://www.iana.org/assignments/relation/" => "iana",
        "https://vocabs.acdh.oeaw.ac.at/schema#" => "acdh",
        "https://id.acdh.oeaw.ac.at/" => "acdhID",
        "http://purl.org/dc/elements/1.1/" => "dc",
        "http://purl.org/dc/terms/" => "dct",
        "http://purl.org/dc/terms/" => "dcterms",
        "http://purl.org/dc/terms/" => "dcterm",
        "http://www.w3.org/2002/07/owl#" => "owl",
        "http://xmlns.com/foaf/0.1/" => "foaf",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf",
        "http://www.w3.org/2004/02/skos/core#" => "skos",
        //"http://xmlns.com/foaf/spec/" => "foaf"
    );
    
    
    public function __construct()
    {
        $this->generalFunctions = new GeneralFunctions();
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        //$this->langConf = $this->config('acdh_repo_gui.settings');
    }
    
    /**
     * Create shortcut from the property for the gui
     *
     * @param string $prop
     * @return string
     */
    protected function createShortcut(string $prop): string
    {
        $prefix = array();
        $prefix = explode('#', $prop);
        $property = end($prefix);
        $prefix = $prefix[0];
        if (isset(self::$prefixesToChange[$prefix.'#'])) {
            return self::$prefixesToChange[$prefix.'#'].':'.$property;
        }
        return '';
    }
    
    /**
     * Create gui inside uri from the identifier
     *
     * @param string $data
     * @return string
     */
    protected function makeInsideUri(string $data): string
    {
        if (!empty($data)) {
            return $this->generalFunctions->detailViewUrlDecodeEncode($data, 1);
        }
        return "";
    }
    
    /**
     * Extend the actual object with the shortcuts
     *
     */
    protected function extendActualObj(bool $root = false)
    {
        $result = array();
      
        foreach ($this->data as $d) {
            
            //add the language to every resource
            $lang = 'en';
            if (isset($d->language) && (!empty($d->language))) {
                $lang = $d->language;
            } else {
                $lang = $this->siteLang;
            }
            // if we have an external type then the repoid will be the value
            //because we will use their own id for the  linking
            //and the value will be the relvalue because of the relational data
            if (isset($d->type) && !empty($d->type) && $d->type == "REL") {
                $d->repoid = "";
                $d->repoid = $d->value;
                $d->value = $d->relvalue;
            }
            
            if (is_null($d->property) === false) {
                //create the shortcur
                $d->title = "";
                $d->title = $d->value;
                if (isset($d->relvalue) && !empty($d->relvalue)) {
                    $d->title = $d->relvalue;
                }
                if (isset($d->acdhid) && !empty($d->acdhid)) {
                    $d->insideUri = "";
                    $d->insideUri = $this->makeInsideUri($d->acdhid);
                }
                $d->shortcut = $this->createShortcut($d->property);
                
                //if we have vocabsid then it will be the uri, to forward the users to the vocabs website
                if (isset($d->vocabsid) && !empty($d->vocabsid)) {
                    $d->uri = $d->vocabsid;
                    unset($d->insideUri);
                }
                $result[$d->shortcut][$lang][] = $d;
            } elseif (isset($d->type) && !empty($d->type) && $d->type == "ID") {
                //setup the acdh uuid variable
                $d->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
                if (strpos($d->value, '/id.acdh.oeaw.ac.at/uuid/') !== false) {
                    $d->acdhid = $d->value;
                }
                if (strpos($d->value, '//repo.') !== false) {
                    $d->repoid = $d->id;
                }
                //the uri for the identifier urls
                if (strpos($d->value, 'http') !== false) {
                    $d->uri = $d->value;
                }
                //add the identifier into the final data
                $result['acdh:hasIdentifier'][$lang][] = $d;
            } 
        }
        if ($root == true) {
            ksort($result);
        }
        $this->data = $result;
    }
    
    abstract public function createView(): array;
}
