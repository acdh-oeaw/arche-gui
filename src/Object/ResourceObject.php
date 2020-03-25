<?php

namespace Drupal\acdh_repo_gui\Object;

class ResourceObject {
    private $config;
    private $properties;
    private $acdhid;
    private $repoid;
    private $repoUrl;
   
    public function __construct(array $data, $config) {
        $this->properties = array();
        $this->config = $config;
        foreach($data as $k => $v) {
            $this->setData($k, $v);  
        }
    }
    public function getData(string $property): array {
        return (isset($this->properties[$property]) && !empty($this->properties[$property])) ? $this->properties[$property] : array();
    }
    
    /**
     * 
     * Change property data
     * 
     * @param string $prop
     * @param array $v
     */
    private function setData(string $prop = null, array $v = null) {
        if(
            isset($prop) && count((array)$v) > 0  
        ) {
            $this->properties[$prop] = $v;
        }
    }
    
    /**
     * 
     * Resource title
     * @return string
     */
    public function getTitle(): string {
        return (isset($this->properties["acdh:hasTitle"][0]->title) && !empty($this->properties["acdh:hasTitle"][0]->title)) ? $this->properties["acdh:hasTitle"][0]->title : "";
    }
    
    /**
     * All identifiers
     * 
     * @return array
     */
    public function getIdentifiers(): array {
        return (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) ? $this->properties["acdh:hasIdentifier"] : array();
    }
    
    /**
     * PID
     * 
     * @return string
     */
    public function getPid(): string {
        return (isset($this->properties["acdh:hasPid"][0]->title) && !empty($this->properties["acdh:hasPid"][0]->title)) ? $this->properties["acdh:hasPid"][0]->title : "";
    }
    
     /**
     * Get resource inside uri
     * 
     * @return string
     */
    public function getInsideUrl(): string {
        if(isset($this->properties["acdh:hasIdentifier"])){
            foreach($this->properties["acdh:hasIdentifier"] as $v){
                if(isset($v->acdhid) && !empty($v->acdhid) ) {
                    return str_replace('https://', '', $v->acdhid);
                }
            }
        }
        return "";
    }
    
    public function getAvailableDate(): string {
        if(isset($this->properties["acdh:hasAvailableDate"])){
            foreach($this->properties["acdh:hasAvailableDate"] as $v){
                if(isset($v->value)){
                    $time = strtotime($v->value);
                    return date('d m Y',$time);
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
    public function getUUID(): string {
        if(isset($this->properties["acdh:hasIdentifier"])){
            foreach($this->properties["acdh:hasIdentifier"] as $v){
                if(isset($v->acdhid) && !empty($v->acdhid) ) {
                    return $v->acdhid;
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
    public function getRepoUrl(): string {
        if(!isset($this->repoid) && empty($this->repoid)){
            $this->getRepoID();
        }
        return $this->config->getBaseUrl().$this->repoid;
    }
    
    /**
     * Get the repo identifier
     * @return string
     */
    public function getRepoID(): string {
        if(isset($this->properties["acdh:hasIdentifier"])){
            foreach($this->properties["acdh:hasIdentifier"] as $v){
                if(isset($v->id) && !empty($v->id)){
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
    public function getAccessRestriction(): array {
        $result = array();
        if(isset($this->properties["acdh:hasAccessRestriction"])){
            
            foreach($this->properties["acdh:hasAccessRestriction"] as $v){
                if( isset($v->title) && !empty($v->title) ) {
                    $result['title'] = $v->title;
                }
                
                if( isset($v->accessrestriction) && !empty($v->accessrestriction) ) {
                   $result['uri'] = $v->accessrestriction;
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
    public function getTitleImage(): string {
        if(isset($this->properties["acdh:hasTitleImage"]) && count($this->properties["acdh:hasTitleImage"]) > 0) {
            if (isset($this->properties["acdh:hasTitleImage"][0]->value)) {
                $img = ''; 
                if($img = @file_get_contents($this->config->getBaseUrl().$this->properties["acdh:hasTitleImage"][0]->value)) {
                    if(!empty($img)) {
                        return $imageData = base64_encode($img);
                    }
                }              
            }
        }
        return '';
    }
    
    /**
     * Get the acdh type string
     * 
     * @return string
     */
    public function getAcdhType(): string {
        if(isset($this->properties["rdf:type"])){
            foreach($this->properties["rdf:type"] as $v){
                if(isset($v->title) && !empty($v->title) && (strpos($v->title, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false) ) {
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
    public function getExpertTableData(): array {
        return $this->properties;
    }
}
