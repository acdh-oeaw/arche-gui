<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of DisseminationServicesHelper
 *
 * @author norbertczirjak
 */
class DisseminationServicesHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;

    private $data;
    private $repoid;
    private $repoUrl;
    private $result = array();
    private $collectionDate;
    private $collectionTmpDir;
    private $additionalData = array();

    /**
     *
     * @param array $additionalData we pass here the additional data for the resources
     * f.e. colelction root data for the tree view
     */
    private function setAdditionalData(array $additionalData = array())
    {
        $this->additionalData = $additionalData;
    }

    private function setRepoUrlId(string $identifier = '')
    {
        $this->repoid = $identifier;
        $this->repoUrl = $this->repo->getBaseUrl() . $this->repoid;
    }

    /**
     *
     * @param array $data
     * @param string $dissemination
     * @param string $identifier
     * @param array $additionalData
     * @return array
     */
    public function createView(array $data = array(), string $dissemination = '', string $identifier = '', array $additionalData = array()): array
    {
        $this->setRepoUrlId($identifier);
        $this->setAdditionalData($additionalData);

        switch ($dissemination) {
            case 'collection':
                $this->data = $data;
                $this->createCollection();
                break;
            case '3d':
                $this->threeDDissService();
                break;
            case 'iiif':
                $this->result['lorisUrl'] = $this->getLorisUrl();
                break;
            case 'collection_lazy':
                $this->data = $data;
                $this->formatCollectionLazyDataStructure();
                break;
            default:
                break;
        }
        return $this->result;
    }

    /**
     * Get the loris url for the loris disserv viewer
     *
     * @return string
     */
    private function getLorisUrl(): string
    {
        $dissServices = $this->generalFunctions->getDissServices($this->repoid);

        foreach ($dissServices as $k => $v) {
            if ($k == "IIIF Endpoint" && isset($dissServices[$k]['uri'])) {
                return $dissServices[$k]['uri'];
            }
        }
        return '';
    }

    /**
     * 3d dissemination service function
     *
     * @return type
     */
    private function threeDDissService()
    {
        $client = new \GuzzleHttp\Client(['verify' => false]);
        $this->result = array();
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
                                $tmpDir = \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . "://") . "/" . $dir . "/";
                                //if the file dir is not exists then we will create it
                                // and we will download the file
                                if (!file_exists($tmpDir) || !file_exists($tmpDir . '/' . $filename)) {
                                    mkdir($tmpDir, 0777);
                                    $file = fopen($tmpDir . '/' . $filename, "w");
                                    fwrite($file, $response->getBody());
                                    fclose($file);
                                } else {
                                    //if the file is not exists
                                    if (!file_exists($tmpDir . '/' . $filename)) {
                                        $file = fopen($tmpDir . '/' . $filename, "w");
                                        fwrite($file, $response->getBody());
                                        fclose($file);
                                    }
                                }
                                $url = '/sites/default/files/' . $dir . '/' . $filename;
                                $this->result['result'] = $url;
                                $this->result['error'] = "";
                            }
                        } else {
                            $this->result['error'] = t('File extension') . ' ' . t('Error');
                            $this->result['result'] = "";
                        }
                    }
                } else {
                    $this->result['error'] = t('No files available.');
                    $this->result['result'] = "";
                }
            });
            $promise->wait();
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            $this->result['error'] = $ex->getMessage();
        }
    }

    private function formatCollectionLazyDataStructure()
    {
        if (count($this->data) > 0) {
            foreach ($this->data as $k => $v) {
                $v['uri'] = $v['id'];
                $v['uri_dl'] = $this->repo->getBaseUrl() . $v['id'];
                $v['text'] = $v['title'];
                $v['resShortId'] = $v['id'];
                if ($v['accesres'] == 'public') {
                    $v['userAllowedToDL'] = true;
                } else {
                    $v['userAllowedToDL'] = false;
                }
                if (empty($v['filename'])) {
                    $v['dir'] = true;
                    $v['children'] = true;
                } else {
                    $v['dir'] = false;
                    $v['icon'] = "jstree-file";
                }
                $v['accessRestriction'] = $v['accesres'];
                $v['encodedUri'] = $this->repo->getBaseUrl() . $v['id'];
                $this->result[$k] = $v;
            }
        } else {
            $this->result[0] = array("uri" => 0, "text" => "There are no child elements",
                "userAllowedToDL" => false, "dir" => false, "children" => false);
        }
    }

    /////// Collection data functions Start ///////

    /**
     * function for the collection data steps
     */
    private function createCollection()
    {
        $this->modifyCollectionDataStructure();
        $this->result = $this->createTreeData($this->data, $this->repoid);
    }

    /**
     * Modify the collection data structure for the tree view
     *
     */
    private function modifyCollectionDataStructure()
    {
        foreach ($this->data as $k => $v) {
            $v['uri'] = $v['mainid'];
            $v['uri_dl'] = $this->repo->getBaseUrl() . $v['mainid'];
            $v['text'] = $v['title'];
            $v['resShortId'] = $v['mainid'];
            if ($v['accesres'] == 'public') {
                $v['userAllowedToDL'] = true;
            } else {
                $v['userAllowedToDL'] = false;
            }
            if (empty($v['filename'])) {
                $v['dir'] = true;
            } else {
                $v['dir'] = false;
                $v['icon'] = "jstree-file";
            }
            $v['accessRestriction'] = $v['accesres'];
            $v['encodedUri'] = $this->repo->getBaseUrl() . $v['mainid'];
            $this->data[$k] = $v;
        }
    }

    /**
     * Creates the tree data for the collection download views
     * @param array $data
     * @param string $identifier
     * @return array
     */
    private function createTreeData(array $data, string $identifier): array
    {
        $tree = array();
        $rootTitle = 'main';
        //if we have a definied root title then we use that
        if (isset($this->additionalData['title'])) {
            $rootTitle = $this->additionalData['title'];
        }


        $first = array(
            "mainid" => $identifier,
            "uri" => $identifier,
            "uri_dl" => $this->repo->getBaseUrl() . $identifier,
            "filename" => "main",
            "resShortId" => $identifier,
            "title" => $rootTitle,
            "text" => $rootTitle,
            "parentid" => '',
            "userAllowedToDL" => true,
            "dir" => true,
            "accessRestriction" => 'public',
            "encodedUri" => $this->repo->getBaseUrl() . $identifier
        );

        $new = array();
        foreach ($data as $a) {
            $a = (array) $a;
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
        foreach ($parent as $k => $l) {
            if (isset($list[$l['mainid']])) {
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
            $request = $client->request('GET', $this->repoUrl . '/metadata', ['Accept' => ['application/n-triples']]);
            if ($request->getStatusCode() == 200) {
                $body = "";
                $body = $request->getBody()->getContents();
                if (!empty($body)) {
                    if (class_exists('EasyRdf_Graph')) {
                        $graph = new \EasyRdf_Graph();
                    } else {
                        $graph = new \EasyRdf\Graph();
                    }
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

    /**
     * Generate tar file from the selected files
     *
     * @param type $binaries
     * @param type $repoid
     * @return string
     */
    public function collectionDownload(array $binaries, string $repoid, string $username = '', string $password = ''): string
    {
        $this->repoUrl = $this->repo->getBaseUrl() . $repoid;
        //1. setup tmp dir
        if ($this->collectionCreateDlDirectory() === false) {
            return '';
        }
        //2. download the selected files
        $this->collectionDownloadFiles($binaries, $username, $password);
        //3. add the turtle file into the collection
        if ($this->collectionGetTurtle() === false) {
            \Drupal::logger('acdh_repo_gui')->notice('collection turtle file generating error' . $this->repoUrl);
        }
        //4. tar the files
        //5. remove the downloaded files and leave just the tar file.
        if ($this->collectionTarFiles() === false) {
            return false;
        }
        $wwwurl = str_replace('/api/', '', $this->repo->getBaseUrl());
        return $wwwurl . '/browser/sites/default/files/collections/' . $this->collectionDate . '/collection.tar';
    }

    private function createPathForCollectionDownload(string $path): string
    {
        $exp = explode("/", $path);
        $last = end($exp);
        return str_replace($last, "", $path);
    }

    /**
     * Remove the white spaces from the filename
     * @param string $filename
     * @return string
     */
    private function createFileNameForCollectionDownload(string $filename): string
    {
        $exp = explode("/", $filename);
        $last = end($exp);
        
        $file = "";
        if (strpos($last, '.') !== false) {
            $file = ltrim($last);
            $file = str_replace(' ', "_", $file);
        } else {
            $file = ltrim($filename);
            $file = str_replace(' ', "_", $file);
        }
        return $file;
    }

    /**
     * Create the directory for the collection
     * @param string $path
     * @return string
     */
    private function createCollectionDir(string $path): string
    {
        $dir = "";

        if (!file_exists($this->collectionTmpDir . $this->collectionDate)) {
            mkdir($this->collectionTmpDir . $this->collectionDate, 0777);
            $dir = $this->collectionTmpDir . $this->collectionDate;
        }

        if (!empty($path)) {
            $path = preg_replace('/\s+/', '_', $path);
            mkdir($this->collectionTmpDir . $this->collectionDate . '/' . $path, 0777, true);
            $dir = $this->collectionTmpDir . $this->collectionDate . '/' . $path;
        }

        return $dir;
    }

    /**
     * Download the selected binaries
     *
     * @param array $binaries
     * @param string $username
     * @param string $password
     */
    public function collectionDownloadFiles(array $binaries, string $username = '', string $password = '')
    {
        $client = new \GuzzleHttp\Client(['auth' => [$username, $password], 'verify' => false]);
        ini_set('max_execution_time', 1800);

        foreach ($binaries as $b) {
            if (isset($b['path']) && isset($b['filename'])) {
                $url = $this->repo->getBaseUrl() . "/" . $b['uri'];
                $path = $b['path'];
                $filename = $this->createFileNameForCollectionDownload($b['filename']);
                $dir = $this->createCollectionDir($path);
                
                try {
                    $resource = fopen($this->collectionTmpDir . $this->collectionDate . '/' . $path . '/' . $filename, 'w');
                    $client->request('GET', $url, ['save_to' => $resource]);
                    chmod($this->collectionTmpDir . $this->collectionDate . '/' . $path . '/' . $filename, 0777);
                } catch (\GuzzleHttp\Exception\ClientException $ex) {
                    \Drupal::logger('acdh_repo_gui')->notice('collection dl error:' . $ex->getMessage() . " " . $url);
                    continue;
                } catch (\GuzzleHttp\Exception\ServerException $ex) {
                    \Drupal::logger('acdh_repo_gui')->notice('collection dl error:' . $ex->getMessage() . " " . $url);
                    //the file is empty
                    continue;
                } catch (\RuntimeException $ex) {
                    \Drupal::logger('acdh_repo_gui')->notice('collection dl error:' . $ex->getMessage() . " " . $url);
                    continue;
                }
            } elseif (isset($b['path'])) {
                mkdir($this->collectionTmpDir . $this->collectionDate . '/' . $b['path'], 0777);
            }
        }
    }

    /**
     * Get the turtle file and copy it to the collection download directory
     *
     * @return bool
     */
    private function collectionGetTurtle(): bool
    {
        $ttl = '';
        $ttl = $this->turtleDissService();
        if (!empty($ttl)) {
            $turtleFile = fopen($this->collectionTmpDir . $this->collectionDate . '/turtle.ttl', "w");
            fwrite($turtleFile, $ttl);
            fclose($turtleFile);
            chmod($this->collectionTmpDir . $this->collectionDate . '/turtle.ttl', 0777);
        } else {
            return false;
        }
        return true;
    }

    /**
     * TAR the downloaded collection files
     * @return bool
     */
    private function collectionTarFiles(): bool
    {
        ini_set('xdebug.max_nesting_level', 2000);
        //if we have files in the directory
        $dirFiles = scandir($this->collectionTmpDir . $this->collectionDate);
        
        if (count($dirFiles) > 0) {
            chmod($this->collectionTmpDir . $this->collectionDate, 0777);
            $archiveFile = $this->collectionTmpDir . $this->collectionDate . '/collection.tar';
            $file = fopen($archiveFile, "w");
            fclose($file);
            chmod($archiveFile, 0777);
            try {
                $tar = new \Drupal\Core\Archiver\Tar($archiveFile);
                foreach ($dirFiles as $d) {
                    if ($d == "." || $d == ".." || $d == 'collection.tar') {
                        continue;
                    } else {
                        $tarFilename = $d;
                        //if the filename is bigger than 100chars, then we need
                        //to shrink it
                        if (strlen($d) > 100) {
                            $ext = pathinfo($d, PATHINFO_EXTENSION);
                            $tarFilename = str_replace($ext, '', $d);
                            $tarFilename = substr($tarFilename, 0, 90);
                            $tarFilename = $tarFilename . '.' . $ext;
                        }
                        chdir($this->collectionTmpDir . $this->collectionDate . '/');
                        $tar->add($d);
                    }
                }
                $this->collectionRemoveTempFiles();
            } catch (Exception $e) {
                \Drupal::logger('acdh_repo_gui')->notice('collection tar files error:' . $e->getMessage());
                return false;
            }
            return true;
        }
        return false;
    }

    /**
     * Remove the files from the collections directory
     */
    private function collectionRemoveTempFiles()
    {
        //get the collection directory
        $dir = $this->collectionTmpDir . $this->collectionDate;
        $it = new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($it, \RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            //remove the directory
            if ($file->isDir()) {
                rmdir($file->getRealPath());
            } else {
                //if the file is the extracted collection then we will keep it
                if (strpos($file->getRealPath(), '/collection.tar') === false) {
                    unlink($file->getRealPath());
                }
            }
        }
    }

    /**
     * Setup the collection directory for the downloads
     *
     * @param string $dateID
     * @return string
     */
    private function collectionCreateDlDirectory(): bool
    {
        $this->collectionDate = date("Ymd_his");
        //the main dir
        $this->collectionTmpDir = \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . "://") . "/collections/";
        
        //if the main directory is not exists
        if (!file_exists($this->collectionTmpDir)) {
            if (!@mkdir($this->collectionTmpDir, 0777)) {
                \Drupal::logger('acdh_repo_gui')->notice('cant create directory: ' . $this->collectionTmpDir);
                return false;
            }
        }
        //if we have the main directory then create the sub
        if (file_exists($this->collectionTmpDir)) {
            //create the actual dir
            if (!file_exists($this->collectionTmpDir . $this->collectionDate)) {
                if (!@mkdir($this->collectionTmpDir . $this->collectionDate, 0777)) {
                    \Drupal::logger('acdh_repo_gui')->notice('cant create directory: ' . $this->collectionDate);
                    return false;
                }
            }
        }
        return true;
    }
}
