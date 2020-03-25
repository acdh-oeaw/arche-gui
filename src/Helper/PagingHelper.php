<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of PagingHelper
 *
 * @author nczirjak
 */
class PagingHelper {
    
    private $data;
    
    public function createView(array $data = array()): array {
        $this->data = new \stdClass();
        $this->data->limit = (int)$data['limit'];
        $this->data->page = (int)$data['page'];
        $this->data->order = $data['order'];
        $this->data->numPage = (int)$data['numPage'];
        $this->data->sum = (int)$data['sum'];
        
        $this->data->pager = $this->createPaginationHTML();
        return array($this->data);
    }
    
    public function createPaginationHTML()
    {
        $out = "";
        $page = $this->data->page;
        if (ceil($this->data->sum / $this->data->limit) > 0){ 
            $out .= '<ul class="pagination">';
            $out .= '<li class="pagination-item"><a id="first-btn" data-pagination="1"><i class="material-icons">first_page</i></a></li>';
            
            
            if ($page > 1) {
                $np = $page - 1;
                $out .= '<li class="pagination-item"><a id="prev-btn" data-pagination='.$np.'><i class="material-icons">chevron_left</i></a></li>';
            }else {
                $out .= '<li class="pagination-item"><i class="material-icons">chevron_left</i></li>';
            }
            
            if ($page < ceil($this->data->sum / $this->data->limit)) {
                $np = $page+1;
                $out .= '<li class="pagination-item"><a id="next-btn" data-pagination='.$np.'><i class="material-icons">chevron_right</i></a></li>';
            }else {
                $out .= '<li class="pagination-item"><i class="material-icons">chevron_right</i></li>';
            }
            $out .= '<li class="pagination-item"><a id="last-btn" data-pagination='.$this->data->numPage.'><i class="material-icons">last_page</i></a></li>';
            $out .= '</ul>';
        }
     
        return $out;
    }
}
