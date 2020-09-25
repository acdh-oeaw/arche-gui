<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * https://redmine.acdh.oeaw.ac.at/issues/11771 - CITE Widget
 *
 * @author nczirjak
 */
class CiteHelper {

    private $cite = array();
    private $repo;
    private $obj;

    public function __construct(\acdhOeaw\acdhRepoLib\Repo $repo, \Drupal\acdh_repo_gui\Object\ResourceObject $obj) {
        $this->repo = $repo;
        $this->obj = $obj;
    }
    
    /**
     * Get the property data from the object as a string
     * 
     * @param object $obj
     * @param string $property
     * @return string
     */
    private function getCiteWidgetData(object $obj, string $property): string {
        $result = "";

        if (count((array) $obj) > 0) {
            if (!empty($obj->getData($property))) {
                foreach ($obj->getData($property) as $key => $val) {
                    if (count((array) $obj->getData($property)) > 0) {
                        if (isset($val->title)) {
                            $result .= $val->title;
                            if ($key + 1 != count($obj->getData($property))) {
                                $result .= ", ";
                            }
                        } elseif (isset($val->acdhid)) {
                            $result .= $val->acdhid;
                            if ($key + 1 != count($obj->getData($property))) {
                                $result .= ", ";
                            }
                        } else {
                            if (!is_array((array) $val)) {
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
     * Create the Collection / project Cite widget 
     * EXAMPLE:
     * Yoshida Sayuri, Klaus Bieber, Gertrude Bieber. The postcard collections of Friedrich Julius Bieber. 
     * ARCHE, https://id.acdh.oeaw.ac.at/fjbieber-postcards. Accessed 16 Jun 2020.
     * @return array
     */
    public function createCiteWidgetCollectionProject(): array {
        $this->cite["MLA"]["string"] = "";
        //hasAuthor/hasCreator, hasPrincipalInvestigator. hasTitle. hasHosting, hasPid/hasIdentifier. Accessed on "current date".
        //get authors/creators
        $authors = "";
        $creators = "";
        $authors = $this->getCiteWidgetData($this->obj, "acdh:hasAuthor");
        if (!empty($authors)) {
            $this->cite["MLA"]["authors"] = $authors;
            $this->cite["MLA"]["string"] = $authors;
            $this->cite["MLA"]["string"] .= ". ";
        } else {
            //Get creator(s)
            $creators = $this->getCiteWidgetData($this->obj, "acdh:hasCreator");
            if (!empty($creators)) {
                $this->cite["MLA"]["creators"] = $creators;
                $this->cite["MLA"]["string"] = $creators;
                $this->cite["MLA"]["string"] .= ". ";
            }
        }

        //get hasPrincipalInvestigator
        $hasPrincipalInvestigator = $this->getCiteWidgetData($this->obj, "acdh:hasPrincipalInvestigator");
        if (!empty($hasPrincipalInvestigator)) {
            //remove the . and change it to ,
            $this->cite["MLA"]["string"] = substr($this->cite["MLA"]["string"], 0, -2);
            $this->cite["MLA"]["string"] .= ", ";
            $this->cite["MLA"]["hasPrincipalInvestigator"] = $hasPrincipalInvestigator;
            $this->cite["MLA"]["string"] .= $hasPrincipalInvestigator;
            $this->cite["MLA"]["string"] .= ". ";
        }

        //Get title
        if (!empty($this->obj->getTitle())) {
            $this->cite["MLA"]["hasTitle"] = $this->obj->getTitle();
            $this->cite["MLA"]["string"] .= $this->obj->getTitle();
            $this->cite["MLA"]["string"] .= ". ";
        }

        $hasHosting = $this->getCiteWidgetData($this->obj, "acdh:hasHosting");
        if (!empty($hasHosting)) {
            $this->cite["MLA"]["hasHosting"] = $hasHosting;
            $this->cite["MLA"]["string"] .= $hasHosting;
            $this->cite["MLA"]["string"] .= ", ";
        }


        if (!empty($this->obj->getPid())) {
            $this->cite["MLA"]["hasPid"] = $this->obj->getPid();
            $this->cite["MLA"]["string"] .= $this->obj->getPid();
            $this->cite["MLA"]["string"] .= ". ";
        } elseif (!empty($this->obj->getAcdhID())) {
            $this->cite["MLA"]["hasIdentifier"] = $this->obj->getAcdhID();
            $this->cite["MLA"]["string"] .= $this->obj->getAcdhID();
            $this->cite["MLA"]["string"] .= ". ";
        }


        $this->cite["MLA"]["string"] .= "Accessed on " . date('d M Y') . ".";
        return $this->cite;
    }

    /**
     * Create the Resource / Metadata Cite widget 
     * 
     * EXAMPLE:
     * Friedrich Julius Bieber, 023_FJB_1902-006a.tif. Digital file created by Sayuri Yoshida. 
     * In: Sayuri, Yoshida, Klaus Bieber, Gertrude Bieber. The postcard collections of Friedrich Julius Bieber. 
     * ARCHE, https://repo.hephaistos.arz.oeaw.ac.at/browser/oeaw_detail/52249. Accessed 16 Jun 2020.    
     * 
     * @param object $topCollection
     * @return array
     */
    public function createCiteWidgetResourceMetadata(object $topCollection): array {
        //hasAuthor/hasCreator. hasTitle. Digitised by hasDigitisingAgent. In: hasCreator(TopCollection), hasPrincipleInvestigator(TopCollection), hasTitle(TopCollection), hasHosting, hasPid. Accessed on "current date".

        $this->cite["MLA"]["string"] = "";
        
        //get authors/creators
        $authors = "";
        $creators = "";
        $authors = $this->getCiteWidgetData($this->obj, "acdh:hasAuthor");
        if (!empty($authors)) {
            $this->cite["MLA"]["authors"] = $authors;
            $this->cite["MLA"]["string"] = $authors;
            $this->cite["MLA"]["string"] .= ". ";
        } else {
            //Get creator(s)
            $creators = $this->getCiteWidgetData($this->obj, "acdh:hasCreator");
            if (!empty($creators)) {
                $this->cite["MLA"]["creators"] = $creators;
                $this->cite["MLA"]["string"] = $creators;
                $this->cite["MLA"]["string"] .= ". ";
            }
        }
        
        //Get title
        if (!empty($this->obj->getTitle())) {
            $this->cite["MLA"]["hasTitle"] = $this->obj->getTitle();
            $this->cite["MLA"]["string"] .= $this->obj->getTitle();
            $this->cite["MLA"]["string"] .= ". ";
        }
        
        $hasDigitisingAgent = $this->getCiteWidgetData($this->obj, "acdh:hasDigitisingAgent");
        if (!empty($hasDigitisingAgent)) {
            $this->cite["MLA"]["hasDigitisingAgent"] = $hasDigitisingAgent;
            $this->cite["MLA"]["string"] .= "Digitised by ".$hasDigitisingAgent;
            $this->cite["MLA"]["string"] .= ".";
        }
        
        //we have top collection data
        //In: hasCreator(TopCollection), hasPrincipleInvestigator(TopCollection), hasTitle(TopCollection)
        if(count((array)$topCollection) > 0) {
           
            $hasCreator = $this->getCiteWidgetData($topCollection, "acdh:hasCreator");
            if (!empty($hasCreator)) {
                $this->cite["MLA"]["topCreator"] = $hasCreator;
                $this->cite["MLA"]["string"] .= "In: ".$hasCreator;
                $this->cite["MLA"]["string"] .= ". ";
            }
            
            $hasPrincipleInvestigator = $this->getCiteWidgetData($topCollection, "acdh:hasPrincipleInvestigator");
            if (!empty($hasPrincipleInvestigator)) {
                $this->cite["MLA"]["topPrincipleInvestigator"] = $hasPrincipleInvestigator;
                $this->cite["MLA"]["string"] .= $hasPrincipleInvestigator;
                $this->cite["MLA"]["string"] .= ". ";
            }
            
            if (!empty($topCollection->getTitle())) {
                $this->cite["MLA"]["topTitle"] = $topCollection->getTitle();
                $this->cite["MLA"]["string"] .= $topCollection->getTitle();
                $this->cite["MLA"]["string"] .= ". ";
            }
        }       
        
        $hasHosting = $this->getCiteWidgetData($this->obj, "acdh:hasHosting");
        if (!empty($hasHosting)) {
            $this->cite["MLA"]["hasHosting"] = $hasHosting;
            $this->cite["MLA"]["string"] .= $hasHosting;
            $this->cite["MLA"]["string"] .= ", ";
        }

        if (!empty($this->obj->getPid())) {
            $this->cite["MLA"]["hasPid"] = $this->obj->getPid();
            $this->cite["MLA"]["string"] .= $this->obj->getPid();
            $this->cite["MLA"]["string"] .= ". ";
        } elseif (!empty($this->obj->getAcdhID())) {
            $this->cite["MLA"]["hasIdentifier"] = $this->obj->getAcdhID();
            $this->cite["MLA"]["string"] .= $this->obj->getAcdhID();
            $this->cite["MLA"]["string"] .= ". ";
        }


        $this->cite["MLA"]["string"] .= "Accessed on " . date('d M Y') . ".";
        return $this->cite;
    }

    /**
     *
     * Create the HTML content of the cite-this widget on single resource view
     *
     * @return array $this->cite Returns the cite-this widget as HTML
     */
    public function createCiteThisWidget(): array {
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
        $authors = $this->getCiteWidgetData($this->obj, "acdh:hasAuthor");
        if (!empty($authors)) {
            $this->cite["MLA"]["authors"] = $authors;
        }

        //Get creator(s)
        $creators = "";
        $creators = $this->getCiteWidgetData($this->obj, "acdh:hasCreator");
        if (!empty($creators)) {
            $this->cite["MLA"]["creators"] = $creators;
        }

        //Get contributor(s)
        $contributors = "";
        $contributors = $this->getCiteWidgetData($this->obj, "acdh:hasContributor");
        if (!empty($contributors)) {
            $this->cite["MLA"]["contributors"] = $contributors;
        }

        //Get PrincipalInvestigator(s)
        $principalInvestigator = "";
        $principalInvestigator = $this->getCiteWidgetData($this->obj, "acdh:hasPrincipalInvestigator");
        if (!empty($principalInvestigator)) {
            $this->cite["MLA"]["hasPrincipalInvestigator"] = $principalInvestigator;
        }

        //Get title
        if (!empty($this->obj->getTitle())) {
            $this->cite["MLA"]["title"] = $this->obj->getTitle();
        }

        //Get isPartOf
        if (!empty($this->obj->getData("acdh:isPartOf"))) {
            $isPartOf = $this->obj->getData("acdh:isPartOf")[0]->title;
            $this->cite["MLA"]["isPartOf"] = $isPartOf;
        }

        //Get hasHosting
        if (!empty($this->obj->getData("acdh:hasHosting"))) {
            $hasHosting = $this->obj->getData("acdh:hasHosting")[0]->title;
            $this->cite["MLA"]["hasHosting"] = $hasHosting;
        }

        /* Get hasPid & create copy link
         * Order of desired URIs:
         * PID > id.acdh > id.acdh/uuid > long gui url
         */
        if (!empty($this->obj->getPID())) {
            $this->cite["MLA"]["acdhURI"] = $this->obj->getUUID();
        }

        if (!$this->cite["MLA"]["acdhURI"]) {
            if (!empty($this->obj->getIdentifiers()) && count($this->obj->getIdentifiers()) > 0) {
                $acdhURIs = $this->obj->getIdentifiers();
                //Only one value under acdh:hasIdentifier
                $uuid = "";

                foreach ($acdhURIs as $id) {
                    if (isset($id->value)) {
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
        if (!empty($this->obj->getData("acdh:hasAvailableDate"))) {
            $availableDate = $this->obj->getData("acdh:hasAvailableDate")[0]->title;
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
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["authors"] . '... ';
        } elseif (isset($this->cite["MLA"]["creators"]) && !empty($this->cite["MLA"]["creators"])) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["creators"] . '. ';
        } elseif (isset($this->cite["MLA"]["contributors"]) && !empty($this->cite["MLA"]["contributors"])) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["contributors"] . '. ';
        }

        //hasPrincipalInvestigator
        if (
                isset($this->cite["MLA"]["hasPrincipalInvestigator"]) &&
                !empty(trim($this->cite["MLA"]["hasPrincipalInvestigator"]))) {
            $this->cite["MLA"]["string"] = str_replace(".", ",", $this->cite["MLA"]["string"]);

            $arr = explode(",", $this->cite["MLA"]["string"]);
            foreach ($arr as $a) {
                $a = ltrim($a);
                //if the string already contains the prininv name, then we will skip it from the final result
                if (!empty($a) && strpos($this->cite["MLA"]["hasPrincipalInvestigator"], $a) !== false) {
                    $this->cite["MLA"]["hasPrincipalInvestigator"] = str_replace($a . ",", "", $this->cite["MLA"]["hasPrincipalInvestigator"]);
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
                $this->cite["MLA"]["string"] .= ' ' . $this->cite["MLA"]["hasPrincipalInvestigator"] . '. ';
            }
        }

        if (substr(trim($this->cite["MLA"]["string"]), -1) == ",") {
            $this->cite["MLA"]["string"] = trim($this->cite["MLA"]["string"]);
            $this->cite["MLA"]["string"] = rtrim($this->cite["MLA"]["string"], ",");
            $this->cite["MLA"]["string"] .= '. ';
        }

        //TITLE
        if ($this->cite["MLA"]["title"]) {
            $this->cite["MLA"]["string"] .= '<em>' . $this->cite["MLA"]["title"] . '.</em> ';
        }

        //PUBLISHER
        if ($this->cite["MLA"]["hasHosting"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["hasHosting"] . ', ';
        }

        //DATE
        if ($this->cite["MLA"]["availableDate"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["availableDate"] . ', ';
        }

        //HANDLE
        if ($this->cite["MLA"]["acdhURI"]) {
            $this->cite["MLA"]["string"] .= $this->cite["MLA"]["acdhURI"] . '. ';
        }

        //DATE
        if ($this->cite["MLA"]["accesedDate"]) {
            $this->cite["MLA"]["string"] .= 'Accessed ' . date('d M Y') . '. ';
        }
        return $this->cite;
    }

}
