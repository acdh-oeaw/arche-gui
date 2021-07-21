<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of BreadCrumbObject
 *
 * @author nczirjak
 */
class BreadCrumbObject {
    
    private $data = array();
    private $length = 0;
    
    public function __construct(array $data) {
        $this->data = $data;
        $this->length = count((array)$data);
    }
    
    /**
     * Generate the breadcrumb navigation to the gui
     * @return string
     */
    public function getBreadCrumb(): string {
        $res = "";
        foreach($this->data as $k => $v) {
            if($v->parenttitle) {
                $res .= "<a id='archeHref' href='/browser/oeaw_detail/".$v->parentid."' title='".$v->parenttitle."'>".$this->createTitle($k, $v)."</a> ";
                if($this->length -1 >= (int)$k) {
                    $res.= "/";
                }
            }
        }
        return $res;
    }
    
    /**
     * Create the breadcrumb title
     * @param int $k
     * @param object $v
     * @return string
     */
    private function createTitle(int $k, object $v): string {
        if($this->length > 3 && $k > 0 && $k < $this->length -1) {
            return "...";
        } else {
            return $v->parenttitle;
        }
    }
    
}
