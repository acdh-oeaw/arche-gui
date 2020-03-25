<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of FormHelper
 *
 * @author nczirjak
 */
class FormHelper {
    
    private $schema = 'https://vocabs.acdh.oeaw.ac.at/schema#';
    
    /**
     * Format the entitiy SQL result
     * @param array $data
     * @return array
     */
    public function formatEntityYears(array $data, bool $years = false): array {
        $result = array();
        $fields = array();
        if(count($data) > 0) {
            foreach($data as $k => $v) {
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
    
    
    
    
}
