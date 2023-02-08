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
    private $tmpDir;
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
        $this->setTmpDir();

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
        $obj = new \Drupal\acdh_repo_gui\Object\ThreeDObject();
        $this->result = $obj->downloadFile($this->repoUrl, $this->tmpDir);
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

  
    private function setTmpDir()
    {
        if (empty($this->tmpDir)) {
            $this->tmpDir = \Drupal::service('file_system')->realpath(\Drupal::config('system.file')->get('default_scheme') . "://");
        }
    }
}
