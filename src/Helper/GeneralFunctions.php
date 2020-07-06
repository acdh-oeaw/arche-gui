<?php

namespace Drupal\acdh_repo_gui\Helper;

use acdhOeaw\acdhRepoLib\Repo;

/**
 * Description of GeneralFunctions
 *
 * @author nczirjak
 */
class GeneralFunctions
{
    private $langConf;
    private $config;
    private $repo;
    
    public function __construct()
    {
        $this->langConf = \Drupal::config('oeaw.settings');
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
    }
    
    /**
     *
     * Encode or decode the detail view url
     *
     * @param string $uri
     * @param bool $code : 0 - decode / 1 -encode
     * @return string
    */
    public function detailViewUrlDecodeEncode(string $data, int $code = 0): string
    {
        if (empty($data)) {
            return "";
        }
      
        if ($code == 0) {
            //if we have the repo id then we need to add the repo baseurl
            if (strpos($data, ':') === false) {
                if (strpos($data, '&') !== false) {
                    $pos = strpos($data, '&');
                    $data = substr($data, 0, $pos);
                    return $this->repo->getBaseUrl().$data;
                }
                return $this->repo->getBaseUrl().$data;
            }
            
            $data = explode(":", $data);
            $identifier = "";

            foreach ($data as $ra) {
                if (strpos($ra, '&') !== false) {
                    $pos = strpos($ra, '&');
                    $ra = substr($ra, 0, $pos);
                    $identifier .= $ra."/";
                } else {
                    $identifier .= $ra."/";
                }
            }
            
            switch (true) {
                case strpos($identifier, 'id.acdh.oeaw.ac.at/uuid/') !== false:
                    $identifier = str_replace('id.acdh.oeaw.ac.at/uuid/', $this->repo->getSchema()->__get('drupal')->uuidNamespace, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    break;
                case strpos($identifier, 'id.acdh.oeaw.ac.at/') !== false:
                    $identifier = str_replace('id.acdh.oeaw.ac.at/', $this->repo->getSchema()->__get('drupal')->idNamespace, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    break;
                case strpos($identifier, 'hdl.handle.net') !== false:
                    $identifier = str_replace('hdl.handle.net/', $this->repo->getSchema()->__get('drupal')->epicResolver, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier, true);
                    break;
                case strpos($identifier, 'geonames.org') !== false:
                    $identifier = str_replace('geonames.org/', $this->repo->getSchema()->__get('drupal')->geonamesUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'd-nb.info') !== false:
                    $identifier = str_replace('d-nb.info/', $this->repo->getSchema()->__get('drupal')->dnbUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'viaf.org/') !== false:
                    $identifier = str_replace('viaf.org/', $this->repo->getSchema()->__get('drupal')->viafUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'orcid.org/') !== false:
                    $identifier = str_replace('orcid.org/', $this->repo->getSchema()->__get('drupal')->orcidUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'pleiades.stoa.org/') !== false:
                    $identifier = str_replace('pleiades.stoa.org/', $this->repo->getSchema()->__get('drupal')->pelagiosUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'gazetteer.dainst.org/') !== false:
                    $identifier = str_replace('gazetteer.dainst.org/', $this->repo->getSchema()->__get('drupal')->gazetteerUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
                case strpos($identifier, 'doi.org/') !== false:
                    $identifier = str_replace('doi.org/', $this->repo->getSchema()->__get('drupal')->doiUrl, $identifier);
                    $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
                    $identifier = $this->specialIdentifierToUUID($identifier);
                    break;
            }
            return $identifier;
        }
        
        if ($code == 1) {
            if (strpos($data, 'hdl.handle.net') !== false) {
                $data = str_replace("http://", "", $data);
            } elseif (strpos($data, $this->repo->getBaseUrl()) !== false) {
                $data = str_replace($this->repo->getBaseUrl(), "", $data);
            } elseif (strpos($data, 'https') !== false) {
                $data = str_replace("https://", "", $data);
            } else {
                $data = str_replace("http://", "", $data);
            }
            return $data;
        }
    }
    
    
    /**
     * This function is get the acdh identifier by the PID, because all of the functions
     * are using the identifier and not the pid :)
     *
     * @param string $identifier
     * @return string
     */
    private function specialIdentifierToUUID(string $identifier, bool $pid = false): string
    {
        $return = "";
        $model = new \Drupal\acdh_repo_gui\Model\GeneralFunctionsModel();
        
        try {
            $idsByPid = $model->getViewData($identifier);
        } catch (Exception $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return "";
        } catch (\InvalidArgumentException $ex) {
            drupal_set_message($ex->getMessage(), 'error');
            return "";
        }
        
        if (count($idsByPid) > 0) {
            foreach ($idsByPid as $d) {
                $return = $this->repo->getBaseUrl().$d->id;
            }
        }
        return $return;
    }
    
    /**
    *
    * Create nice format from file sizes
    *
    * @param type $bytes
    * @return string
    */
    public function formatSizeUnits(string $bytes): string
    {
        if ($bytes >= 1073741824) {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        } elseif ($bytes > 1) {
            $bytes = $bytes . ' bytes';
        } elseif ($bytes == 1) {
            $bytes = $bytes . ' byte';
        } else {
            $bytes = '0 bytes';
        }

        return $bytes;
    }
    
    /**
     * Extend the collection download python script with the url
     *
     * @param string $fdUrl
     * @return string
     */
    public function changeCollDLScript(string $repoUrl)
    {
        $text = "";
        try {
            $fileName = \Drupal::request()->getSchemeAndHttpHost().'/browser/sites/default/files/coll_dl_script/collection_download_repo.py';
            $text = @file_get_contents($fileName);
            if (empty($text)) {
                return $text;
            }
            
            if (strpos($text, '{ingest.location}') !== false) {
                $text = str_replace("{ingest.location}", $this->repo->getSchema()->ingest->location, $text);
            }
            
            if (strpos($text, '{fileName}') !== false) {
                $text = str_replace("{fileName}", $this->repo->getSchema()->fileName, $text);
            }
            
            if (strpos($text, '{parent}') !== false) {
                $text = str_replace("{parent}", $this->repo->getSchema()->parent, $text);
            }
            if (strpos($text, '{metadataReadMode}') !== false) {
                $text = str_replace("{metadataReadMode}", 'X-METADATA-READ-MODE', $text);
            }
            
            if (strpos($text, 'args = args.parse_args()') !== false) {
                $text = str_replace("args = args.parse_args()", "args = args.parse_args(['".$repoUrl."', '--recursive'])", $text);
            }
            
            return $text;
        } catch (\Exception $e) {
            return;
        }
        return $text;
    }
    
    /**
    * Get the dissemination services
    *
    * @param string $id
    * @return array
    */
    public function getDissServices(string $id): array
    {
        $result = array();
        //internal id
        $repodb = \acdhOeaw\acdhRepoLib\RepoDb::factory($this->config);
        $repDiss = new \acdhOeaw\arche\disserv\RepoResourceDb($this->repo->getBaseUrl().$id, $repodb);
        try {
            $dissServ = array();
            $dissServ = $repDiss->getDissServices();
            foreach ($dissServ as $k => $v) {
                //we need to remove the gui from the diss serv list because we are on the gui
                if (strtolower($k) != 'gui') {
                    $result[$k] = (string) $v->getRequest($repDiss)->getUri();
                }
            }
            return $result;
        } catch (Exception $ex) {
            return array();
        } catch (\GuzzleHttp\Exception\ServerException $ex) {
            return array();
        } catch (\acdhOeaw\acdhRepoLib\exception\RepoLibException $ex) {
            return array();
        }
    }
}
