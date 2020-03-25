<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;
/**
 * Description of ApiModel
 *
 * @author nczirjak
 */
class ChildApiModel extends ArcheModel {
    
    private $repodb;
    private $result = array();
    private $data = array();
    
    public function __construct() {
        //set up the DB connections
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }
    
    /**
     * Get the actual page view data
     * 
     * @param string $identifier
     * @param int $limit
     * @param int $page
     * @param string $orderby
     * @return array
     */
    public function getViewData(string $identifier = "", int $limit = 10, int $page = 0, string $orderby = "titleasc" ): array {
        $order = $this->ordering($orderby);
        $prop = $order->property;
        $ord = $order->order;
        
        //get the requested sorting
        try {
            $query = $this->repodb->query(
                    "select * from child_views_func(:id, :limit, :offset, :order, :property)", 
                    array(':id' => $identifier,  ':limit' => $limit, ':offset' => $page, ':order' => $ord, ':property' => $prop)
            );
            
            $this->result = $query->fetchAll();
            $this->reorderResult();
           
        } catch (Exception $ex) {
            $this->data = array();
        } catch(\Drupal\Core\Database\DatabaseExceptionWrapper $ex ) {
            $this->data = array();
        }
        
        $this->changeBackDBConnection();
        return $this->data;
    }
    
    /**
     * Create the order values for the sql
     * 
     * @param string $orderby
     * @return object
     */
    private function ordering(string $orderby = "titleasc"): object {
        $result = new \stdClass();
        $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
        $result->order = 'asc';
        
        if($orderby == "titleasc") {
            $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
            $result->order = 'asc';
        }else if ($orderby == "titledesc") {
            $result->property = 'https://vocabs.acdh.oeaw.ac.at/schema#hasTitle';
            $result->order = 'desc';
        }else if ($orderby == "dateasc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'asc';            
        }else if ($orderby == "datedesc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'desc';
        }
        return $result;
    }
    
    /**
     * Get the number of the child resources for the pagination
     * 
     * @param string $identifier
     */
    public function getCount(string $identifier): int {
        
        try {
            $query = $this->repodb->query("select num from child_view_sum_func(:id)", array(':id' => $identifier));
            $result = $query->fetch();
            if(isset($result->num)) {
                return (int)$result->num;
            }
        } catch (Exception $ex) {
            return 0;
        } catch(\Drupal\Core\Database\DatabaseExceptionWrapper $ex ) {
            return 0;
        }
        $this->changeBackDBConnection();
        return 0;
    }
    
    /**
     * Reorder the sql result based on the orderid
     */
    private function reorderResult() {
        if(count((array)$this->result) > 0) {
            foreach($this->result as $v) {
                if(isset($v->orderid)){
                    $this->data[$v->orderid][] = $v;
                }
            }
        }
    }
}
