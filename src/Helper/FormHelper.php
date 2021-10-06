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
    
    /**
     * Format the category data for the form checkboxes
     * @param array $data
     * @return array
     */
    public function formatCategoryTypes(array $data): array
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
                $result['data'][$k]->id = $v->id;
                $fields[$this->formatCategoryTitleForValue($title).':'.$v->id] =  $v->value." (".$v->count.")";
            }
        }
        $result['fields'] = $fields;
        return $result;
    }
    
    /**
     * Transform the string to remove special chars
     * @param string $string
     * @return string
     */
    private function formatCategoryTitleForValue(string $string): string
    {
        $string = str_replace(' ', '-', $string); // Replaces all spaces with hyphens.
        return preg_replace('/[^A-Za-z0-9\-]/', '-', $string);
    }
    
    /**
     * Do we have to recache the data?!
     * @param string $type
     * @param string $modifyDate
     * @return bool
     */
    public function checkCacheData(string $type, string $modifyDate): bool
    {
        //get the cache
        $cache = \Drupal::cache()->get('archeCacheSF_'.$type);
        //if we have cache then check the last modification date.
        if (!$cache) {
            return true;
        } elseif (isset($cache->tags[0]) && isset($cache->tags[1])) {
            $mdTime = date('Y-m-d H:i:s', strtotime($modifyDate));
            if ($cache->tags[0].' '.$cache->tags[1] < $mdTime) {
                return true;
            }
        }
        return false;
    }
}
