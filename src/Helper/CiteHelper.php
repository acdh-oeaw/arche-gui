<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * https://redmine.acdh.oeaw.ac.at/issues/11771 - CITE Widget
 *
 * @author nczirjak
 */
class CiteHelper
{
    private $cite = array();
    private $repo;
    private $obj;

    public function __construct(\acdhOeaw\acdhRepoLib\Repo $repo, \Drupal\acdh_repo_gui\Object\ResourceObject $obj)
    {
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
    private function getCiteWidgetData(object $obj, string $property): string
    {
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
     * 
     * Remove the duplications
     * 
     * @param array $arr1
     * @param array $arr2
     * @return string
     */
    private function removeDuplication(array $arr1, array $arr2): string {
        
        $intersecArray = array();
        $str = "";
        $intersecArray = array_intersect($arr1,$arr2);
        if(count($intersecArray) > 0) {
            foreach($intersecArray as $v) {
                foreach (array_keys($arr2, $v, true) as $key) {
                    unset($arr2[$key]);
                }
            }
            $str = implode(', ', $arr2);
        }
        return $str;
    }

    /**
     * Create the Collection / project Cite widget
     * EXAMPLE:
     * Yoshida Sayuri, Klaus Bieber, Gertrude Bieber. The postcard collections of Friedrich Julius Bieber.
     * ARCHE, https://id.acdh.oeaw.ac.at/fjbieber-postcards. Accessed 16 Jun 2020.
     * @return array
     */
    public function createCiteWidgetCollectionProject(): array
    {
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
                        
            $str = "";
            //remove the . and change it to ,
            $this->cite["MLA"]["string"] = substr($this->cite["MLA"]["string"], 0, -2);
            
            //if we already have an author/creator, then we need to filter out the duplications
            if(!empty($this->cite["MLA"]["string"])) {
                if (strpos((string)$hasPrincipalInvestigator, strval($this->cite["MLA"]["string"])) !== false) {
                    $str = $this->removeDuplication(explode(',',$this->cite["MLA"]["string"]),  explode(',',$hasPrincipalInvestigator));
                }
            }else {
                $str = $hasPrincipalInvestigator;
            }
            //if we already have an author and now we have a prinInv then use the ,
            if(!empty($this->cite["MLA"]["string"]) && !empty($str)) {
               $this->cite["MLA"]["string"] .= ", "; 
            }
            
            $this->cite["MLA"]["hasPrincipalInvestigator"] = $str;
            $this->cite["MLA"]["string"] .= $str;
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
    public function createCiteWidgetResourceMetadata(object $topCollection): array
    {
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
        if (count((array)$topCollection) > 0) {
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
}
