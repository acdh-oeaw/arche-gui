<?php

namespace Drupal\acdh_repo_gui\Helper;

use Drupal\acdh_repo_gui\Helper\ArcheHelper as Helper;

/**
 * Description of FormHelper
 *
 * @author nczirjak
 */
class FormHelper
{
    private $schema = 'https://vocabs.acdh.oeaw.ac.at/schema#';
    
    /**
     * Format the search filter Years for the GUI
     * @param array $data
     * @return array
     */
    public function formatEntityYears(array $data, bool $years = false): array
    {
        $result = array();
        $fields = array();
        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $result['data'][$k] = new \stdClass();
                $result['data'][$k]->count = $v->count;
                $value = ($years) ? $v->year : $v->value ;
                $title = ltrim(str_replace($this->schema, "", $value));
                $result['data'][$k]->title = $title;
                $result['data'][$k]->uri = $value;
                $fields[$title] = $title." (".$v->count.")";
            }
        }
        $result['fields'] = $fields;
        return $result;
    }
    
    /**
     * Format the search filter types for the GUI
     * @param array $data
     * @return array
     */
    public function formatEntityTypes(array $data): array
    {
        $result = array();
        $fields = array();
        if (count($data) > 0) {
            foreach ($data as $k => $v) {
                $result['data'][$k] = new \stdClass();
                $result['data'][$k]->count = $v->count;
                $title = ltrim(str_replace($this->schema, "", $v->value));
                $result['data'][$k]->title = $title;
                $result['data'][$k]->uri = $v->value;
                $fields[Helper::createShortcut($v->value)] = $title." (".$v->count.")";
            }
        }
        $result['fields'] = $fields;
        return $result;
    }
}
