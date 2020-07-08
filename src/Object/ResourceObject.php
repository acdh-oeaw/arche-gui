<?php

namespace Drupal\acdh_repo_gui\Object;

use GuzzleHttp;

class ResourceObject
{
    private $config;
    private $properties;
    private $acdhid;
    private $repoid;
    private $repoUrl;
    private $language;
    private $thumbUrl = 'https://arche-thumbnails.acdh.oeaw.ac.at/';
   
    public function __construct(array $data, $config, string $language = 'en')
    {
        $this->properties = array();
        $this->config = $config;
        $this->language = $language;
        
        foreach ($data as $k => $v) {
            if (isset($v[$language])) {
                $this->setData($k, $v[$language]);
            } else {
                if (($language == 'en') && isset($v['de'])) {
                    $this->setData($k, $v['de']);
                } elseif (($language == 'de') && isset($v['en'])) {
                    $this->setData($k, $v['en']);
                }
            }
        }
        
        //set acdhid /repoid / repourl
    }
    
    /**
     * get the data based on the property
     *
     * @param string $property
     * @return array
     */
    public function getData(string $property): array
    {
        return (isset($this->properties[$property]) && !empty($this->properties[$property])) ? $this->properties[$property] : array();
    }
    
    /**
     *
     * Change property data
     *
     * @param string $prop
     * @param array $v
     */
    private function setData(string $prop = null, array $v = null)
    {
        if (
            isset($prop) && count((array)$v) > 0
        ) {
            $this->properties[$prop] = $v;
        }
    }
    
    /**
     * Get the Resource title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return (isset($this->properties["acdh:hasTitle"][0]->title) && !empty($this->properties["acdh:hasTitle"][0]->title)) ? $this->properties["acdh:hasTitle"][0]->title : "";
    }
    
    /**
     * All identifiers
     *
     * @return array
     */
    public function getIdentifiers(): array
    {
        return (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) ? $this->properties["acdh:hasIdentifier"] : array();
    }
    
    /**
     * Get all identifiers which are not acdh related
     *
     * @return type
     */
    public function getNonAcdhIdentifiers()
    {
        $result = array();
        if (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $k => $v) {
                //filter out the baseurl related identifiers and which contains the id.acdh
                if ((strpos($v->value, $this->config->getBaseUrl()) === false) &&
                        (strpos($v->value, 'https://id.acdh.oeaw.ac.at') === false)
                    ) {
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
    
    
    
    /**
     * PID
     *
     * @return string
     */
    public function getPid(): string
    {
        return (isset($this->properties["acdh:hasPid"][0]->title) && !empty($this->properties["acdh:hasPid"][0]->title)) ? $this->properties["acdh:hasPid"][0]->title : "";
    }
    
    /**
    * Get resource inside uri
    *
    * @return string
    */
    public function getInsideUrl(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->acdhid) && !empty($v->acdhid)) {
                    return str_replace('https://', '', $v->acdhid);
                }
            }
        }
        return "";
    }
    
    /**
     * Get the available date in a specified format
     * @return string
     */
    public function getAvailableDate(): string
    {
        if (isset($this->properties["acdh:hasAvailableDate"])) {
            foreach ($this->properties["acdh:hasAvailableDate"] as $v) {
                if (isset($v->value)) {
                    $time = strtotime($v->value);
                    return date('d m Y', $time);
                }
            }
        }
        return "";
    }
   
    
    /**
     * Get the resource acdh uuid
     *
     * @return string
     */
    public function getUUID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->acdhid) && !empty($v->acdhid)) {
                    return $v->acdhid;
                }
            }
        }
        return "";
    }
    
    /**
     * Get the resource acdh id
     *
     * @return string
     */
    public function getAcdhID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (strpos($v->value, '/id.acdh.oeaw.ac.at/') !== false) {
                    return $v->value;
                }
            }
        }
        return "";
    }
    
    /**
     * Get the full repo url with the identifier for the actual resource
     *
     * @return string
     */
    public function getRepoUrl(): string
    {
        if (!isset($this->repoid) && empty($this->repoid)) {
            $this->getRepoID();
        }
        return $this->config->getBaseUrl().$this->repoid;
    }
    
    /**
     * Get the Gui related url for the resource
     * @return string
     */
    public function getRepoGuiUrl(): string
    {
        if (!isset($this->repoid) && empty($this->repoid)) {
            $this->getRepoID();
        }
        return str_replace('/api/', '/browser/oeaw_detail/', $this->config->getBaseUrl()).$this->repoid;
    }
    
    /**
     * Get the repo identifier
     * @return string
     */
    public function getRepoID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->id) && !empty($v->id)) {
                    $this->repoid = $v->id;
                    return $v->id;
                }
            }
        }
        return "";
    }
    
    
    /**
     * Get the accessrestriction url and title
     *
     * @return array
     */
    public function getAccessRestriction(): array
    {
        $result = array();
        if (isset($this->properties["acdh:hasAccessRestriction"])) {
            foreach ($this->properties["acdh:hasAccessRestriction"] as $v) {
                if (isset($v->title) && !empty($v->title)) {
                    $result['title'] = $v->title;
                }
                
                if (isset($v->accessrestriction) && !empty($v->accessrestriction)) {
                    $result['uri'] = $v->accessrestriction;
                }
                if (isset($v->vocabsid) && !empty($v->vocabsid)) {
                    $result['vocabsid'] = $v->vocabsid;
                }
            }
        }
        return $result;
    }
    
    /**
     * get the title image url
     *
     * @return string
     */
    public function getTitleImage(string $width = '200px'): string
    {
        $img = '';
        //check the thumbnail service first
        if ($acdhid = $this->getAcdhID()) {
            $acdhid = str_replace('http://', '', $acdhid);
            $acdhid = str_replace('https://', '', $acdhid);
            if ($file = @fopen($this->thumbUrl.$acdhid, "r")) {
                $type = fgets($file, 40);
                if (!empty($type)) {
                    $width = str_replace('px', '', $width);
                    $img = $this->thumbUrl.$acdhid.'?width='.$width;
                    return '<img src="'.$img.'" class="img-responsive">';
                }
            }
        }
        
        //if there is no thumbnail servicees then we will download the image
        if (isset($this->properties["acdh:hasTitleImage"]) && count($this->properties["acdh:hasTitleImage"]) > 0) {
            if (isset($this->properties["acdh:hasTitleImage"][0]->value)) {
                if (!empty($this->properties["acdh:hasTitleImage"][0]->value)) {
                    if ($file = @fopen($this->config->getBaseUrl().$this->properties["acdh:hasTitleImage"][0]->value, "r")) {
                        $type = fgets($file, 40);
                        if (!empty($type)) {
                            if (strpos(strtolower($type), 'svg') === false) {
                                $img = '<img src="'.$this->config->getBaseUrl().$this->properties["acdh:hasTitleImage"][0]->value.'" class="img-responsive" style="max-width: '.$width.';" /> ';
                            } else {
                                $imgBinary = '';
                                if ($imgBinary = @file_get_contents($this->config->getBaseUrl().$this->properties["acdh:hasTitleImage"][0]->value)) {
                                    if (!empty($imgBinary)) {
                                        $img = '<img src="data:image/png;base64,'.base64_encode($imgBinary).'" class="img-responsive" style="max-width: '.$width.';" /> ';
                                    }
                                }
                            }
                        }
                        fclose($file);
                    }
                }
                return $img;
            }
        }
        return '';
    }
    
    /**
     * Check if we have a titleimage id or not
     * @return bool
     */
    public function isTitleImage(): bool
    {
        if (isset($this->properties["acdh:hasTitleImage"]) && count($this->properties["acdh:hasTitleImage"]) > 0) {
            if (isset($this->properties["acdh:hasTitleImage"][0]->value)) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * Get the acdh type string
     *
     * @return string
     */
    public function getAcdhType(): string
    {
        if (isset($this->properties["rdf:type"])) {
            foreach ($this->properties["rdf:type"] as $v) {
                if (isset($v->title) && !empty($v->title) && (strpos($v->title, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false)) {
                    return str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->title);
                }
            }
        }
        return "";
    }
    
    /**
     * Get all data
     *
     * @return array
     */
    public function getExpertTableData(): array
    {
        return $this->properties;
    }
    
    /**
     * Format the date values for the twig template
     *
     * @param string $property
     * @param string $dateFormat
     * @return string
     */
    public function getFormattedDateByProperty(string $property, string $dateFormat = 'Y') : string
    {
        if (isset($this->properties[$property])) {
            if (isset($this->properties[$property][0]->value)) {
                $val = strtotime($this->properties[$property][0]->value);
                return date($dateFormat, $val);
            }
        }
        return '';
    }
    
    /**
     * Select the identifier for the Copy resource link
     * @return string
     */
    public function getCopyResourceLink() : string
    {
        //check the pid
        if (!empty($this->getPid())) {
            return $this->getPid();
        }
        $id = '';
        $otherid = '';
        //check the non acdh identifiers
        if (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $k => $v) {
                //if we have acdh id then we pass that
                if ((strpos($v->value, "/id.acdh.oeaw.ac.at/") !== false)) {
                    $id = $v->value;
                } elseif ((strpos($v->value, $this->config->getBaseUrl()) === false)) {
                    //if we dont have then we pass everything except the repourl based id
                    $otherid =  $v->value;
                }
            }
        }
        
        if (!empty($id)) {
            return $id;
        } elseif (!empty($otherid)) {
            return $otherid;
        }
       
        return "";
    }
}
