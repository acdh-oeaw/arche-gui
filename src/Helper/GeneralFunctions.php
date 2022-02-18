<?php

namespace Drupal\acdh_repo_gui\Helper;

use acdhOeaw\arche\lib\Repo;

/**
 * Description of GeneralFunctions
 *
 * @author nczirjak
 */
class GeneralFunctions
{
    private $config;
    private $repo;
    
    public function __construct($cfg = null)
    {
        ($cfg && is_string($cfg)) ?  $this->config = $cfg : $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui').'/config/config.yaml';
        $this->repo = \acdhOeaw\arche\lib\Repo::factory($this->config);
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
                    $identifier = $this->replaceIdString($identifier, 'id.acdh.oeaw.ac.at/uuid/', $this->repo->getSchema()->namespaces->id.'uuid/');
                    break;
                case strpos($identifier, 'id.acdh.oeaw.ac.at/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'id.acdh.oeaw.ac.at/', $this->repo->getSchema()->namespaces->id);
                    break;
                case strpos($identifier, 'hdl.handle.net') !== false:
                    $identifier = $this->replaceIdString($identifier, 'hdl.handle.net/', 'http://hdl.handle.net/', true);
                    break;
                case strpos($identifier, 'geonames.org') !== false:
                    $identifier = $this->replaceIdString($identifier, 'geonames.org/', 'https://www.geonames.org/', true);
                    break;
                case strpos($identifier, 'd-nb.info') !== false:
                    $identifier = $this->replaceIdString($identifier, 'd-nb.info/', 'http://d-nb.info/', true);
                    break;
                case strpos($identifier, 'viaf.org/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'viaf.org/', 'http://viaf.org/', true);
                    break;
                case strpos($identifier, 'orcid.org/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'orcid.org/', 'https://orcid.org/', true);
                    break;
                case strpos($identifier, 'pleiades.stoa.org/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'pleiades.stoa.org/', 'https://pleiades.stoa.org/', true);
                    break;
                case strpos($identifier, 'gazetteer.dainst.org/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'gazetteer.dainst.org/', 'https://gazetteer.dainst.org/', true);
                    break;
                case strpos($identifier, 'doi.org/') !== false:
                    $identifier = $this->replaceIdString($identifier, 'doi.org/', 'https://doi.org/', true);
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
        } catch (\Exception $ex) {
            \Drupal::messenger()->addError($ex->getMessage());
            return "";
        } catch (\InvalidArgumentException $ex) {
            \Drupal::messenger()->addError($ex->getMessage());
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
    * Get the dissemination services
    *
    * @param string $id
    * @return array
    */
    public function getDissServices(string $id): array
    {
        $result = array();
        //internal id
        $repodb = \acdhOeaw\arche\lib\RepoDb::factory($this->config);
        $repDiss = new \acdhOeaw\arche\lib\disserv\RepoResourceDb($this->repo->getBaseUrl().$id, $repodb);
        try {
            $dissServ = array();
            $dissServ = $repDiss->getDissServices();
            $shown = [];
            foreach ($dissServ as $k => $v) {
                //we need to remove the gui from the diss serv list because we are on the gui
                if (strtolower($k) != 'gui') {
                    $hash = spl_object_hash($v);
                    if (!isset($shown[$hash])) {
                        try {
                            //if the dissemination services has a title then i will use it, if not then the hasReturnType as a label
                            if ($v->getGraph()->get($this->repo->getSchema()->label)->__toString()) {
                                $k = $v->getGraph()->get($this->repo->getSchema()->label)->__toString();
                            }
                            $result[$k]['uri'] = (string) $v->getRequest($repDiss)->getUri();
                            $result[$k]['title'] = (string) $k;
                            //if we have a description then we will use it
                            if ($v->getGraph()->get($this->repo->getSchema()->__get('namespaces')->ontology.'hasDescription')->__toString()) {
                                $result[$k]['description'] = $v->getGraph()->get($this->repo->getSchema()->__get('namespaces')->ontology.'hasDescription')->__toString();
                            }
                            $shown[$hash] = true;
                        } catch (\Exception $ex) {
                            error_log(print_r($ex->getMessage(), true));
                            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
                        }
                    }
                }
            }
            return $result;
        } catch (\Exception $ex) {
            return array();
        } catch (\GuzzleHttp\Exception\ServerException $ex) {
            return array();
        } catch (\acdhOeaw\arche\lib\exception\RepoLibException $ex) {
            return array();
        }
    }
    
    /**
     * Handle the default shibboleth user for the federated login
     *
     * @return void
     */
    public function handleShibbolethUser(string $eppn = "", string $email = ""): void
    {
        $userEmail = "";
        if ($this->checkEmail($eppn)) {
            $shib = user_load_by_mail($eppn);
            $userEmail = $eppn;
        } elseif ($this->checkEmail($email)) {
            $shib = user_load_by_mail($email);
            $userEmail = $email;
        } else {
            $shib = user_load_by_name('shibboleth');
        }
       
        //if we dont have it then we will create it
        if ($shib === false) {
            $this->createShibbolethUser($userEmail);
        } elseif ($shib->id() != 0) {
            $this->loadTheUserData($shib);
        }
    }
    
    /**
     * Load the user from the drupal db
     * @param object $shib
     * @return void
     */
    private function loadTheUserData(object &$shib): void
    {
        $user = \Drupal\User\Entity\User::load($shib->id());
        $user->activate();
        user_login_finalize($user);
    }
    
    /**
     * create the shibboleth users drupal user
     * @param string $email
     * @return void
     */
    private function createShibbolethUser(string $email = ""): void
    {
        $this->checkShibbolethGroup();
        $user = \Drupal\user\Entity\User::create();
        // Mandatory.
        (!empty($email) ? $user->setPassword($this->createShibbiolethUserPwd(9)) : $user->setPassword($this->repo->getSchema()->__get('drupal')->shibbolethPwd));
        $user->enforceIsNew();
        (!empty($email) ? $user->setEmail($email) : $user->setEmail('sh_guest@acdh.oeaw.ac.at'));
        (!empty($email) ? $user->setUsername($email) : $user->setUsername('shibboleth'));
        $user->addRole('shibboleth');
        $user->activate();
        $user->save();
        (!empty($email) ? $shib = user_load_by_name($email) : $shib = user_load_by_name('shibboleth'));
        user_login_finalize($user);
    }
    
    /**
     * check the string for email address
     * @param string $str
     * @return bool
     */
    private function checkEmail(string $str): bool
    {
        if (strpos($str, '@') !== false) {
            return true;
        }
        return false;
    }
    
    /**
     * Generate a new custom password for the new shibboleth drupal user
     * @param int $length
     * @return string
     */
    private function createShibbiolethUserPwd(int $length): string
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        return substr(str_shuffle($chars), 0, $length);
    }
    
    /**
     * Return the json decoded string
     * @param string $json
     * @return array
     */
    public function jsonDecodeData(string $json): array
    {
        return (json_decode($json, true)) ? json_decode($json, true) : array();
    }

    /**
     * Check the shibboleth user role exists or not
     * @return void
     */
    private function checkShibbolethGroup(): void
    {
        $roles = \Drupal\user\Entity\Role::loadMultiple();
        if (!array_key_exists('shibboleth', $roles)) {
            $this->createShobbolethGroup();
        }
    }
    
    /**
     * Create the shibboleth user role
     * @return void
     */
    private function createShobbolethGroup(): void
    {
        $role = \Drupal\user\Entity\Role::create(array('id' => 'shibboleth', 'label' => 'Shibboleth'));
        $role->save();
    }
    
    public function getRepoIdFromApiUrl(string $apiUrl): string
    {
        if (strpos($apiUrl, $this->repo->getBaseUrl()) !== false) {
            return str_replace($this->repo->getBaseUrl(), '', $apiUrl);
        }
        return '';
    }
    
    public function initClarinVcrUrl(): string
    {
        $yaml = \Symfony\Component\Yaml\Yaml::parse(file_get_contents($this->config));
        if (isset($yaml['clarinVcrUrl'])) {
            return $yaml['clarinVcrUrl'];
        }
        return "";
    }

    /**
     * Format the URL identifier to SQL QUERY acceptable format (adding http/https)
     * And if it is a special identifier then we query the acdh identifier
     * @param string $identifier
     * @param string $prop
     * @param string $httpProp
     * @param bool $specialId
     * @return string
     */
    private function replaceIdString(string $identifier, string $prop, string $httpProp, bool $specialId = false): string
    {
        $identifier = str_replace($prop, $httpProp, $identifier);
        $identifier = (substr($identifier, -1) == "/") ? substr_replace($identifier, "", -1) : $identifier;
        if ($specialId) {
            return $this->specialIdentifierToUUID($identifier, true);
        }
        return $identifier;
    }
}
