<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\GeneralFunctions;
use Drupal\acdh_repo_gui\Object\ResourceObject;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoDisserv\RepoResource as RR;

/**
 * Description of ApiViewHelper
 *
 * @author nczirjak
 */
class ChildApiHelper
{
    use \Drupal\acdh_repo_gui\Traits\ArcheUtilTrait;

    private $childViewObjectArray;
    private $data = array();
    private $properties = array();

    /**
     * Child view create
     *
     * @param array $data
     * @return array
     */
    public function createView(array $data = array()): array
    {
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
        $this->setProperties();
        $this->formatResultToGui($data);

        if (count((array) $this->data) == 0) {
            return array();
        }

        foreach ($this->data as $k => $v) {
            $this->childViewObjectArray[] = new ResourceObject($v, $this->repo);
        }
        return $this->childViewObjectArray;
    }

    /**
     * Set the properties what we want to process for the child object
     */
    private function setProperties()
    {
        $this->properties = array(
            'version' => array('shortcut' => 'acdh:hasVersion', 'property' => $this->repo->getSchema()->__get('namespaces')->ontology. "hasVersion"),
            'acdhtype' => array('shortcut' => 'rdf:type', 'property' => $this->repo->getSchema()->__get('namespaces')->rdfs . "type"),
            'accesres' => array('shortcut' => 'acdh:hasAccessRestriction', 'property' => $this->repo->getSchema()->__get('namespaces')->ontology."hasAccessRestriction"),
            'description' => array('shortcut' => 'acdh:hasDescription', 'property' => $this->repo->getSchema()->__get('namespaces')->ontology."hasDescription"),
            'avdate' => array('shortcut' => 'acdh:hasAvailableDate', 'property' => $this->repo->getSchema()->creationDate),
            'title' => array('shortcut' => 'acdh:hasTitle', 'property' => $this->repo->getSchema()->label),
            'id' => array('shortcut' => 'acdh:hasIdentifier', 'property' => $this->repo->getSchema()->__get('id')),
        );
    }

    /**
     * We need to format the root results for the gui
     * @return array
     */
    private function formatResultToGui(array $data)
    {
        if (count((array) $data) > 0) {
            foreach ($data as $k => $v) {
                $lang = 'en';
                if (isset($d->language)) {
                    if (!empty($d->language)) {
                        $lang = $d->language;
                    } else {
                        $lang = $this->siteLang;
                    }
                }
                if (isset($v->id)) {
                    $this->fetchProperties($k, $v, $lang);
                }
            }
        }
    }

    /**
     * Fetch the property values and create the response object
     * @param string $k
     * @param object $v
     * @param string $lang
     * @return void
     */
    private function fetchProperties(string $k, object $v, string $lang): void
    {
        foreach ($this->properties as $pk => $pv) {
            if (isset($v->$pk)) {
                $title = $v->$pk;
                if ($v->$pk == 'accesres') {
                    $title = str_replace("https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/", "", $v->$pk);
                }

                $this->data[$k][$pv['shortcut']][$lang] = array(
                    $this->createObj(
                        $v->id,
                        $pv['property'],
                        $title,
                        $v->$pk
                    )
                );
            }
        }
    }

    /**
     * Create the root object for gui
     * @param int $id
     * @param string $property
     * @param string $title
     * @param string $value
     * @return object
     */
    private function createObj(int $id, string $property, string $title, string $value): object
    {
        $obj = new \stdClass();
        $obj->id = $id;
        $obj->property = $property; //;
        $obj->title = $title;
        $obj->value = $value;
        return $obj;
    }
}
