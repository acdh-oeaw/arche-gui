<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of CiteHelper
 *
 * @author nczirjak
 */
class CiteHelper {
    
    private $cite = array();
    private $config;
    private $repo;
    
    public function __construct(\acdhOeaw\acdhRepoLib\Repo $repo) {
        $this->repo = $repo;
    }
    private function getCiteWidgetData(\Drupal\acdh_repo_gui\Object\ResourceObject $data, string $property): string
    {
        $result = "";
        
        if (count((array)$data) > 0) {
            if (!empty($data->getData($property))) {
                foreach ($data->getData($property) as $key => $val) {
                    if (count((array)$data->getData($property)) > 0) {
                        if (isset($val->title)) {
                            $result .= $val->title;
                            if ($key + 1 != count($data->getData($property))) {
                                $result .= ", ";
                            }
                        } elseif (isset($val->acdhid)) {
                            $result .= $val->acdhid;
                            if ($key + 1 != count($data->getData($property))) {
                                $result .= ", ";
                            }
                        } else {
                            if (!is_array($val)) {
                                $result .= ", " . $val;
                            }
                        }
                    } else {
                        if (isset($val->title)) {
                            $result = $val->title;
                        } elseif (isset($val->acdhid)) {
                            $result = $val->acdhid;
                        } else {
                            $result = $val;
                        }
                    }
                }
            }
        }
        return $result;
    }
    
    /**
     *
     * Create the HTML content of the cite-this widget on single resource view
     *
     * @param array $obj Delivers the properties of the resource
     * @return array $this->cite Returns the cite-this widget as HTML
     */
    public function createCiteThisWidget(\Drupal\acdh_repo_gui\Object\ResourceObject $obj): array
    {
        $content = [];

        /** MLA Format
         * Example:
         * Mörth, Karlheinz. Dictionary Gate. ACDH, 2013, hdl.handle.net/11022/0000-0000-001B-2. Accessed 12 Oct. 2017.
         */
        $this->cite["MLA"] = [
            "authors" => "", "creators" => "", "hasPrincipalInvestigator" => "", 
            "contributors" => "", "title" => "", "isPartOf" => "", "availableDate" => "", 
            "hasHosting" => "", "hasEditor" => "", "accesedDate" => "", "acdhURI" => ""
        ];

        //Get authors(s)
        $authors = "";
        $authors = $this->getCiteWidgetData($obj, "acdh:hasAuthor");
        if (!empty($authors)) {
            $this->cite["MLA"]["authors"] = $authors;
        }
        
        //Get creator(s)
        $creators = "";
        $creators = $this->getCiteWidgetData($obj, "acdh:hasCreator");
        if (!empty($creators)) {
            $this->cite["MLA"]["creators"] = $creators;
        }
        
        //Get contributor(s)
        $contributors = "";
        $contributors = $this->getCiteWidgetData($obj, "acdh:hasContributor");
        if (!empty($contributors)) {
            $this->cite["MLA"]["contributors"] = $contributors;
        }
        
        //Get PrincipalInvestigator(s)
        $principalInvestigator = "";
        $principalInvestigator = $this->getCiteWidgetData($obj, "acdh:hasPrincipalInvestigator");
        if (!empty($principalInvestigator)) {
            $this->cite["MLA"]["hasPrincipalInvestigator"] = $principalInvestigator;
        }

        //Get title
        if (!empty($obj->getTitle())) {
            $this->cite["MLA"]["title"] = $obj->getTitle();
        }

        //Get isPartOf
        if (!empty($obj->getData("acdh:isPartOf"))) {
            $isPartOf = $obj->getData("acdh:isPartOf")[0]->title;
            $this->cite["MLA"]["isPartOf"] = $isPartOf;
        }

        //Get hasHosting
        if (!empty($obj->getData("acdh:hasHosting"))) {
            $hasHosting = $obj->getData("acdh:hasHosting")[0]->title;
            $this->cite["MLA"]["hasHosting"] = $hasHosting;
        }
        
        /* Get hasPid & create copy link
         * Order of desired URIs:
         * PID > id.acdh > id.acdh/uuid > long gui url
         */
        if (!empty($obj->getPID())) {
            $this->cite["MLA"]["acdhURI"] = $obj->getUUID();
        }
        
        if (!$this->cite["MLA"]["acdhURI"]) {
            if (!empty($obj->getIdentifiers()) && count($obj->getIdentifiers()) > 0) {
                $acdhURIs = $obj->getIdentifiers();
                //Only one value under acdh:hasIdentifier
                $uuid = "";

                foreach ($acdhURIs as $id) {
                    if(isset($id->value)) {
                        if (strpos($id->value, $this->repo->getSchema()->__get('drupal')->uuidNamespace) !== false) {
                            $uuid = $id->value;
                        //if the identifier is the normal acdh identifier then return it
                        } elseif (strpos($id->value, $this->repo->getSchema()->__get('id')) !== false) {
                            $uuid = $id->value;
                            break;
                        }
                    }
                }
                $this->cite["MLA"]["acdhURI"] = $uuid;
            }
        }

        //Get available date
        if (!empty($obj->getData("acdh:hasAvailableDate"))) {
            $availableDate = $obj->getData("acdh:hasAvailableDate")[0]->title;
            $availableDate = strtotime($availableDate);
            $this->cite["MLA"]["availableDate"] = date('Y', $availableDate);
        }
        
        //Get accesed date
        $this->cite["MLA"]["accesedDate"] = date('d M Y');

        
        //Process MLA
        //Top level resource
        //if (!$this->cite["MLA"]["isPartOf"]) {

        $this->cite["MLA"]["string"] = "";
        //AUTHORS
        if (isset($this->cite["MLA"]["authors"]) && !empty($this->cite["MLA"]["authors"])) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["authors"].'... ';
        } elseif (isset($this->cite["MLA"]["creators"]) && !empty($this->cite["MLA"]["creators"])) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["creators"].'. ';
        } elseif (isset($this->cite["MLA"]["contributors"]) && !empty($this->cite["MLA"]["contributors"])) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["contributors"].'. ';
        }

        //hasPrincipalInvestigator
        if (
            isset($this->cite["MLA"]["hasPrincipalInvestigator"])
                &&
            !empty(trim($this->cite["MLA"]["hasPrincipalInvestigator"]))) {
            $this->cite["MLA"]["string"] = str_replace(".", ",", $this->cite["MLA"]["string"]);
            
            $arr = explode(",", $this->cite["MLA"]["string"]);
            foreach ($arr as $a) {
                $a = ltrim($a);
                //if the string already contains the prininv name, then we will skip it from the final result
                if (!empty($a) && strpos($this->cite["MLA"]["hasPrincipalInvestigator"], $a) !== false) {
                    $this->cite["MLA"]["hasPrincipalInvestigator"] = str_replace($a.",", "", $this->cite["MLA"]["hasPrincipalInvestigator"]);
                    $this->cite["MLA"]["hasPrincipalInvestigator"] = str_replace($a, "", $this->cite["MLA"]["hasPrincipalInvestigator"]);
                }
            }
            
            //$this->cite["MLA"]["hasPrincipalInvestigator"] = substr(rtrim($this->cite["MLA"]["hasPrincipalInvestigator"]), 0, -1);
            if (isset($this->cite["MLA"]["hasPrincipalInvestigator"]) && !empty(trim($this->cite["MLA"]["hasPrincipalInvestigator"]))) {
                //if the last char is the , then we need to remove it
                if (substr(trim($this->cite["MLA"]["hasPrincipalInvestigator"]), -1) == ",") {
                    $this->cite["MLA"]["hasPrincipalInvestigator"] = trim($this->cite["MLA"]["hasPrincipalInvestigator"]);
                    $this->cite["MLA"]["hasPrincipalInvestigator"] = rtrim($this->cite["MLA"]["hasPrincipalInvestigator"], ",");
                }
                $this->cite["MLA"]["string"] .= ' '.$this->cite["MLA"]["hasPrincipalInvestigator"].'. ';
            }
        }
        
        if (substr(trim($this->cite["MLA"]["string"]), -1) == ",") {
            $this->cite["MLA"]["string"] = trim($this->cite["MLA"]["string"]);
            $this->cite["MLA"]["string"] = rtrim($this->cite["MLA"]["string"], ",");
            $this->cite["MLA"]["string"] .= '. ';
        }

        //TITLE
        if ($this->cite["MLA"]["title"]) {
            $this->cite["MLA"]["string"] .= '<em>'.$this->cite["MLA"]["title"].'.</em> ';
        }

        //PUBLISHER
        if ($this->cite["MLA"]["hasHosting"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["hasHosting"].', ';
        }

        //DATE
        if ($this->cite["MLA"]["availableDate"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["availableDate"].', ';
        }

        //HANDLE
        if ($this->cite["MLA"]["acdhURI"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["acdhURI"].'. ';
        }

        //DATE
        if ($this->cite["MLA"]["accesedDate"]) {
            $this->cite["MLA"]["string"] .= 'Accessed '.$this->cite["MLA"]["accesedDate"].'. ';
        }
        return $this->cite;
    }
}
