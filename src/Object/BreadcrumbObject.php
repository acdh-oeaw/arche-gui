<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of BreadcrumbObject
 *
 * @author nczirjak
 */
class BreadcrumbObject {
    
    private $siteLang;
    private $repo;
    private $obj;
    private $result = array();
    private $data;
    private $id;
    
    public function __construct(array $obj, string $id) {
        $config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml';
        $this->repo = \acdhOeaw\arche\lib\Repo::factory($config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
        $this->obj = $obj;
        $this->data = new \stdClass();
        $this->id = $id;
    }
    
    /**
     * return with the breadcrumb result array
     * @return array
     */
    public function getData(): array {
        if(count($this->obj) == 0){
            return $this->result;
        }
        $this->process();
        //remove the actual resource, we will not display it on the gui
        if(isset($this->result[0])) {
            unset($this->result[0]);
        }
        return array_reverse($this->result);
    }

    /**
     * start the obj process
     */
    private function process() {
        if($this->getMainresource() === true) {
            $this->getParents();
        }
    }

    /**
     * Get the actual resource to we can start to discover the parents
     * @return bool
     */
    private function getMainresource(): bool {
        if(isset($this->obj[$this->repo->getBaseUrl().$this->id])) {
            $this->getParent();
            unset($this->obj[$this->repo->getBaseUrl().$this->id]);
            return true;
        }
        return false;
    }
    
    /**
     * get the first parent for our actual resource
     */
    private function getParent() {
        $obj = new \stdClass();
        $obj->mainid = $this->id;
        $obj->parentid = $this->getParentId($this->id);
        $obj->parenttitle = $this->getParentLabel($this->id);
        $this->result[0] = $obj;
    }

    /**
     * Get the parent id based on the acdh:hasIdentifier and the baseUrl
     * @param string $parentid
     * @return string
     */
    private function getParentId(string $parentid): string {
        if(isset($this->obj[$this->repo->getBaseUrl().$parentid][$this->repo->getSchema()->parent])) {
            foreach($this->obj[$this->repo->getBaseUrl().$parentid][$this->repo->getSchema()->parent] as $id) {
                if (strpos($id['value'], $this->repo->getBaseUrl()) !== false) {
                    return str_replace($this->repo->getBaseUrl(), '', $id['value']);
                }
            }
        }
        return '';
    }

    /**
     * Get the parent title based on the acdh:hasTitle and the site language
     * @param string $parentid
     * @return string
     */
    private function getParentLabel(string $parentid): string {
        if(isset($this->obj[$this->repo->getBaseUrl().$parentid][$this->repo->getSchema()->label])) {
            return $this->getValueTitle($this->obj[$this->repo->getBaseUrl().$parentid][$this->repo->getSchema()->label]);
        }
        return '';
    }
    
    /**
     * get the actual title value
     * @param array $titleArr
     * @return string
     */
    private function getValueTitle(array $titleArr): string {
        //if we have the site actual language as a title then we return with
        //that one, if not then we will use the first value from the array
        foreach ($titleArr as $v) {
            if (isset($v['lang']) && $v['lang'] == $this->siteLang) {
                return $v['value'];
            }
        }
        return $titleArr[0]['value'];
    }

    /**
     * Get all of the other parents with recursion
     */
    private function getParents() {
        $this->searchForParents($this->result[0]->parentid);
    }
    
    /**
     * Recursive search for the parents
     * @param type $parentid
     */
    private function searchForParents($parentid) {
        foreach($this->obj as $k => $v){
            if($k == $this->repo->getBaseUrl().$parentid) {
                $res = new \stdClass();
                $res->parentid = $parentid;
                $res->mainid = $this->getParentId($parentid);
                $res->parenttitle = $this->getParentLabel($parentid);
                unset($this->obj[$parentid]);
                $this->result[] = $res;
                if(isset($this->obj[$this->repo->getBaseUrl().$res->mainid])) {
                    $this->searchForParents($res->mainid);
                }
            }
        }
    }
}
