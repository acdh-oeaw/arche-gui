<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Object\ResourceObject;

/**
 * Description of DetailViewHelper
 *
 * @author nczirjak
 */
class DetailViewHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;
    
    private $detailViewObjectArray;
    
    /**
     * Build up the necessary data for the detail view
     * @param array $data
     * @return array
     */
    public function createView(array $data = array()): array
    {
        $this->data = array();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language'])  : $this->siteLang = "en";
        $this->data = $data;
        
        $this->extendActualObj();
        
        if (count((array)$this->data) == 0) {
            return array();
        }
      
        $this->detailViewObjectArray = array();
        $this->detailViewObjectArray[] = new ResourceObject($this->data, $this->repoDb, $this->siteLang);
        
        return $this->detailViewObjectArray;
    }
    
     
    /**
     * Do the API call ti fetch the detail view data
     * @param string $id
     * @return array
     */
    public function overviewObj(string $id): array {
      
        $client = new \GuzzleHttp\Client();
        try {
          $response = $client->get(str_replace('/api/', '', $this->repoDb->getBaseUrl()).'/browser/api/overview/'.$id.'/en');
          
         
          $data = json_decode($response->getBody(), TRUE);
          $this->data = json_decode(json_encode($data));
          $this->extendActualObj();
          $this->detailViewObjectArray[] = new ResourceObject($this->data, $this->repoDb, $this->siteLang);
        }
        catch (\GuzzleHttp\Exception\RequestException $e) {
          return [];
        }
        return $this->detailViewObjectArray; 
    }
}
