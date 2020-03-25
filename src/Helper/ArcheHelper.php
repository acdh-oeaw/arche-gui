<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Helper\ConfigConstants as CC;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;

/**
 * Description of ArcheHelper
 *
 * @author nczirjak
 */
abstract class ArcheHelper {
    
    protected $generalFunctions;
    protected $config;
    protected $repo;
    
    public function __construct() {
        $this->generalFunctions = new GeneralFunctions();
        $this->config = $_SERVER["DOCUMENT_ROOT"].'/modules/custom/acdh_repo_gui/config.yaml';
        $this->repo = Repo::factory($this->config);
    }
    
    /**
     * Create shortcut from the property for the gui
     * 
     * @param string $prop
     * @return string
     */
    protected function createShortcut(string $prop): string {
        $prefix = array();
        $prefix = explode('#', $prop);
        $property = end($prefix);
        $prefix = $prefix[0];
        if (isset(CC::$prefixesToChange[$prefix.'#'])) {
           return CC::$prefixesToChange[$prefix.'#'].':'.$property;
        }
    }
    
    /**
     * Create gui inside uri from the identifier
     * 
     * @param string $data
     * @return string
     */
    protected function makeInsideUri(string $data): string {
        if(!empty($data)) {
            return $this->generalFunctions->detailViewUrlDecodeEncode($data, 1);
        }
        return "";
    }
    
    /**
     * Extend the actual object with the shortcuts
     * 
     */
    protected function extendActualObj(bool $root = false) {
        $result = array();
        
        foreach($this->data as $d) {
            if(isset($d->type) && !empty($d->type) && $d->type == "REL") {
                $d->repoid = "";
                $d->repoid = $d->value;
            }
            
            if(is_null($d->property) === false) {
                //create the shortcur
                $d->title = "";
                $d->title = $d->value;
                if(isset($d->relvalue) && !empty($d->relvalue)) {
                    $d->title = $d->relvalue;
                }
                if(isset($d->acdhid) && !empty($d->acdhid)) {
                    $d->insideUri = "";
                    $d->insideUri = $this->makeInsideUri($d->acdhid);
                }
                $d->shortcut = $this->createShortcut($d->property);
                $result[$d->shortcut][] = $d;
            }else if(isset($d->type) && !empty($d->type) && $d->type == "ID") {
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
                $result['acdh:hasIdentifier'][] = $d;
            }
            
            
        }
        if($root == true) {
            ksort($result);
        }
        
        $this->data = $result;
    }
    
    abstract public function createView(): array;
}
