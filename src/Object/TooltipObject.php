<?php

namespace Drupal\acdh_repo_gui\Object;

use \Drupal\acdh_repo_gui\Helper\ArcheHelper as Helper;

/**
 * Description of TooltipObject
 *
 * @author nczirjak
 */
class TooltipObject
{
    private $siteLang;
    private $repo;
    private $obj;
    private $result = array();
    private $data;
    
    public function __construct(array $obj)
    {
        $config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml';
        $this->repo = \acdhOeaw\arche\lib\Repo::factory($config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
        $this->obj = $obj;
        $this->data = new \stdClass();
    }
    
    public function getData(): array
    {
        $this->process();
        $this->result = $this->formatTooltip($this->result);
        return $this->result;
    }
    
    private function formatTooltip($tooltip): array
    {
        $result = array();
        foreach ($tooltip as $t) {
            if (isset($t->type)) {
                $result[$t->type] = $t;
            }
        }
        return $result;
    }
    
    
    private function process()
    {
        foreach ($this->obj as $k => $v) {
            $this->data = new \stdClass();
            foreach ($v as $ok => $ov) {
                if ($ok == 'http://www.w3.org/2000/01/rdf-schema#label') {
                    $this->data->title = $this->getValueTitleByProperty($ok, $ov, 'http://www.w3.org/2000/01/rdf-schema#label');
                }
                if ($ok == 'http://www.w3.org/2000/01/rdf-schema#comment') {
                    $this->data->description = $this->getValueTitleByProperty($ok, $ov, 'http://www.w3.org/2000/01/rdf-schema#comment');
                }
                if ($ok == $this->repo->getSchema()->id) {
                    $this->data->type = Helper::createShortcut($this->getToolTipAcdhIdentifier($ov));
                }
                $this->data->id = str_replace($this->repo->getBaseUrl(), '', $k);
            }
            if (isset($this->data->title) && isset($this->data->type)) {
                $this->result[] = $this->data;
            }
        }
    }
    
    /**
     * Tooltip get the values
     * @param string $objectKey
     * @param array $objectValue
     * @param string $property
     * @return string
     */
    private function getValueTitleByProperty(string $objectKey, array $objectValue, string $property): string
    {
        if ($objectKey == $property) {
            return $this->getValueTitle($objectValue);
        }
        return "";
    }

    /**
     * Inside the tooltip data we can get the type from the acdh identifier property
     * @param array $ids
     * @return string
     */
    private function getToolTipAcdhIdentifier(array $ids): string
    {
        foreach ($ids as $i) {
            if (strpos($i['value'], $this->repo->getSchema()->__get('namespaces')->ontology) !== false) {
                return $i['value'];
            }
        }
        return "";
    }

   
    
    private function getValueTitle(array $titleArr): string
    {
        //if we have the site actual language as a title then we return with
        //that one, if not then we will use the first value from the array
        foreach ($titleArr as $v) {
            if (isset($v['lang']) && $v['lang'] == $this->siteLang) {
                return $v['value'];
            }
        }
        return $titleArr[0]['value'];
    }
}
