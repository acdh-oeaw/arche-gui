<?php


namespace Drupal\acdh_repo_gui\Helper;

use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource;
use Drupal\acdh_repo_gui\Helper\ArcheHelper;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;

use EasyRdf\Graph;
use EasyRdf\Resource;

/**
 * Description of DisseminationServicesHelper
 *
 * @author norbertczirjak
 */
class DisseminationServicesHelper extends ArcheHelper {
    
    private $data;
    private $repoid;
    private $repoUrl;
    private $result = array();    
    private $dataFor3dObj = array();    
    


    public function createView(array $data = array(), string $dissemination = '', string $identifier = ''): array {
        
        $this->repoid = $identifier;
        $this->repoUrl = $this->repo->getBaseUrl().$this->repoid;
       
        
        switch ($dissemination) {
            case 'collection':
                $this->data = $data;
                $this->createCollection();
                break;
            case 'turtle_api':
                $this->result = array($this->turtleDissService());
                break;
            case '3d':
                $this->result = $this->threeDDissService();
                break;
            case 'iiif':
                $this->result = $this->getLorisUrl();
                break;
            default:
                break;
        }
        return $this->result;
    }
    
    private function getLorisUrl() {
        
        //$this->generalFunctions->getDissServices($this->repoid);
        return array();
    }
    
    private function threeDDissService() {
        $client = new \GuzzleHttp\Client(['verify' => false]);
        
        try {
            $request = new \GuzzleHttp\Psr7\Request('GET', $this->repoUrl);
            //send async request
            $promise = $client->sendAsync($request)->then(function ($response) {
                if ($response->getStatusCode() == 200) {
                    //get the filename
                    if (count($response->getHeader('Content-Disposition')) > 0) {
                        $txt = explode(";", $response->getHeader('Content-Disposition')[0]);
                        $filename = "";
                        $extension = "";
                        
                        foreach ($txt as $t) {
                            if (strpos($t, 'filename') !== false) {
                                $filename = str_replace("filename=", "", $t);
                                $filename = str_replace('"', "", $filename);
                                $filename = ltrim($filename);
                                $extension = explode(".", $filename);
                                $extension = end($extension);
                                continue;
                            }
                        }

                        if ($extension == "nxs" || $extension == "ply") {
                            if (!empty($filename)) {
                                $dir = str_replace(".", "_", $filename);
                                $tmpDir = $_SERVER['DOCUMENT_ROOT'].'/sites/default/files/'.$dir.'/';
                                //if the file dir is not exists then we will create it
                                // and we will download the file
                                if (!file_exists($tmpDir) || !file_exists($tmpDir.'/'.$filename)) {
                                    mkdir($tmpDir, 0777);
                                    $file = fopen($tmpDir.'/'.$filename, "w");
                                    fwrite($file, $response->getBody());
                                    fclose($file);
                                } else {
                                    //if the file is not exists
                                    if (!file_exists($tmpDir.'/'.$filename)) {
                                        $file = fopen($tmpDir.'/'.$filename, "w");
                                        fwrite($file, $response->getBody());
                                        fclose($file);
                                    }
                                }
                                $url = '/sites/default/files/'.$dir.'/'.$filename;
                                $this->dataFor3dObj['result'] = $url;
                                $this->dataFor3dObj['error'] = "";
                            }
                        } else {
                            $this->dataFor3dObj['error'] = t('File extension').' '.t('Error');
                            $this->dataFor3dObj['result'] = "";
                        }
                    }
                } else {
                    $this->dataFor3dObj['error'] = t('No files available.');
                    $this->dataFor3dObj['result'] = "";
                }
            });
            $promise->wait();
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            $this->dataFor3dObj['error'] = $ex->getMessage();
        }
        return $this->dataFor3dObj;
    }
    
    
    /////// Collection data functions Start ///////
    /**
     * function for the collection data steps
     */
    private function createCollection() {
        $this->modifyCollectionDataStructure();
        $this->result = $this->createTreeData($this->data, $this->repoid);
    }
    
    
    /**
     * Modify the collection data structure for the tree view
     * 
     */
    private function modifyCollectionDataStructure() {
        foreach($this->data as $k => $v) {
            $v['uri'] = $v['mainid'];
            $v['uri_dl'] = $this->repo->getBaseUrl().$v['mainid'];
            $v['text'] = $v['title'];
            $v['resShortId'] = $v['mainid'];
            if($v['accesres'] == 'public'){
                $v['userAllowedToDL'] = true;
            }else {
                $v['userAllowedToDL'] = false;
            }
            if(empty($v['filename'])){
                $v['dir'] = true;
            }else {
                $v['dir'] = false;
            }
            $v['accessRestriction'] = $v['accesres'];
            $v['encodedUri'] = $this->repo->getBaseUrl().$v['mainid'];
            $this->data[$k] = $v;
        }
        
    }
    
    /**
     * Creates the tree data for the collection download views
     * @param array $data
     * @param string $identifier
     * @return array
     */
    private function createTreeData(array $data, string $identifier): array {
        $tree = array();
        
        $first = array(
            "mainid" => $identifier,
            "uri" => $identifier,
            "uri_dl" => $this->repo->getBaseUrl().$identifier,
            "filename" => "main",
            "resShortId" => $identifier,
            "title" => 'main',
            "text" => 'main',
            "parentid" => '',
            "userAllowedToDL" => true,
            "dir" => true,
            "accessRestriction" => 'public',
            "encodedUri" => $this->repo->getBaseUrl().$identifier
        );
        
        $new = array();
        foreach ($data as $a){
            $a = (array)$a;
            $new[$a['parentid']][] = $a;
        }
        $tree = $this->convertToTreeById($new, array($first));
        return $tree;
    }


    /**
     * This func is generating a child based array from a single array by ID
     *
     * @param type $list
     * @param type $parent
     * @return type
     */
    public function convertToTreeById(&$list, $parent)
    {
        $tree = array();
        foreach ($parent as $k=>$l){
            if(isset($list[$l['mainid']])){
                $l['children'] = $this->convertToTreeById($list, $list[$l['mainid']]);
            }
            $tree[] = $l;
        } 
        return $tree;
    }
    
    /////// Collection data functions end ///////
    
    
    /**
      *
      * Create turtle file from the resource
      *
      * @param string $fedoraUrl
      * @return type
      */
    public function turtleDissService()
    {
        
        $result = array();
        $client = new \GuzzleHttp\Client();
        
        try {
            $request = $client->request('GET', $this->repoUrl.'/metadata', ['Accept' => ['application/n-triples']]);
            if ($request->getStatusCode() == 200) {
                $body = "";
                $body = $request->getBody()->getContents();
                if (!empty($body)) {
                    $graph = new \EasyRdf_Graph();
                    $graph->parse($body);
                    return $graph->serialise('turtle');
                }
            }
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            return "";
        } catch (\Exception $ex) {
            return "";
        }
    }
}
