<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of ThreeDObject
 *
 * @author nczirjak
 */
class ThreeDObject
{
    private $client;
    private $tmpDir;
    private $allowedExtension = array("ply", "nxs");
    private $result = array();

    // tmpDir =  \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . "://") . "/"
    public function __construct()
    {
        $this->client = new \GuzzleHttp\Client(['verify' => false]);
    }

    private function setTmpDir(string $tmpDir): void
    {
        $this->tmpDir = $tmpDir;
    }

    public function downloadFile(string $repoUrl, string $tmpDir): array
    {
        $this->setTmpDir($tmpDir);
        try {
            $this->doTheRequest($repoUrl);
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->result = array('error' => $ex->getMessage());
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->result = array('error' => $ex->getMessage());
        }
        
        return $this->result;
    }

    private function doTheRequest(string $repoUrl)
    {
        $request = new \GuzzleHttp\Psr7\Request('GET', $repoUrl);
        //send async request
        
        $promise = $this->client->sendAsync($request)->then(function ($response) {
            if ($response->getStatusCode() == 200) {
                //get the filename
                if (count($response->getHeader('Content-Disposition')) > 0) {
                    $header = $this->getHeaderData($response->getHeader('Content-Disposition'));
                    $this->createFileTmpDir($header['filename']);
                    $this->writeFileContent($response->getBody(), $header['filename']);
                    $url = str_replace('http://', 'https://', \Drupal::request()->getSchemeAndHttpHost()) . '/browser/sites/default/files/tmp_files/' .
                            str_replace(".", "_", $header['filename']) . '/' . $header['filename'];
                    $this->result = array('result' => $url, 'error' => '');
                }
            } else {
                $this->result = array('result' => '', 'error' => t('No files available.'));
            }
        });
        $promise->wait();
    }

    /**
     * get the filename and extension
     * @param array $cd
     * @return array
     * @throws \Exception
     */
    private function getHeaderData(array $cd): array
    {
        if (!isset($cd[0])) {
            return array();
        }

        $txt = explode(";", $cd[0]);

        foreach ($txt as $t) {
            if (strpos($t, 'filename') !== false) {
                $filename = str_replace("filename=", "", str_replace('"', "", ltrim($t)));
                $ext = explode(".", $filename);
                $extension = (string) end($ext);

                if (empty($filename) || empty($extension)) {
                    throw new \Exception(t('No header data available.'));
                } elseif (!in_array($extension, $this->allowedExtension)) {
                    throw new \Exception(t('File extension') . ' ' . t('Error'));
                }
                return array('filename' => $filename, 'extension' => $extension);
            }
        }
        return array();
    }

    /**
     * Create the file temp dir
     * @param string $filename
     * @throws \Exception
     */
    private function createFileTmpDir(string $filename)
    {
        $this->checkTmpDirExists();
        $this->setTmpDir($this->tmpDir . "/tmp_files/" . str_replace(".", "_", $filename) . "/");
        if (!file_exists($this->tmpDir.$filename)) {
            if (!mkdir($this->tmpDir, 0777)) {
                throw new \Exception(\error_get_last());
            }
        }
    }
    
    /**
     * Save the 3d file content
     * @param type $body
     * @param string $filename
     */
    private function writeFileContent($body, string $filename)
    {
        if (!file_exists($this->tmpDir.$filename)) {
            $file = fopen($this->tmpDir . '/' . $filename, "w");
            if (!$file) {
                throw new \Exception(t('File open failed'));
            }
            fwrite($file, $body);
            fclose($file);
        }
    }

    /**
     * Create the main dir if not exists
     * @throws \Exception
     */
    private function checkTmpDirExists()
    {
        if (!file_exists($this->tmpDir)) {
            if (!mkdir($this->tmpDir, 0777)) {
                throw new \Exception(\error_get_last());
            }
        }
    }
}
