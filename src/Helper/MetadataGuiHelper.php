<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of MetadataGuiHelper
 *
 * @author norbertczirjak
 */
class MetadataGuiHelper
{
    private $data = array();
    private $result = array();
    private $siteLang = 'en';
    
    private static $actors_involved = array(
        'hasPrincipalInvestigator', 'hasContact',
        'hasCreator', 'hasAuthor',
        'hasEditor', 'hasContributor',
        'hasFunder', 'hasLicensor',
        'hasMetadataCreator'
    );
     
    private static $coverage = array(
        'hasRelatedDiscipline', 'hasCoverage',
        'hasActor', 'hasSpatialCoverage',
        'hasSubject', 'hasTemporalCoverage',
        'hasTemporalCoverageIdentifier', 'hasCoverageEndDate',
        'hasCoverageStartDate'
    );
    
    private static $right_access = array(
        'hasOwner', 'hasRightsHolder',
        'hasLicense', 'hasAccessRestriction',
        'hasRestrictionRole'
    );
    
    private static $dates = array(
        'hasDate', 'hasStartDate',
        'hasEndDate', 'hasCreatedDate',
        'hasCreatedStartDate', 'hasCreatedEndDate',
        'hasCollectedStartDate', 'hasCollectedEndDate'
    );
    
    private static $relations_other_projects = array(
        'relation', 'hasRelatedProject',
        'hasRelatedCollection', 'continues',
        'isContinuedBy', 'documents',
        'isDocumentedBy', 'hasDerivedPublication',
        'hasMetadata', 'isMetadataFor',
        'hasSource', 'isSourceOf',
        'isNewVersionOf', 'isPreviousVersionOf',
        'hasPart', 'isPartOf',
        'hasTitleImage', 'isTitleImageOf'
    );
    
    private static $curation = array(
        'hasDepositor', 'hasAvailableDate',
        'hasPid', 'hasNumberOfItems',
        'hasBinarySize', 'hasFormat',
        'hasLocationPath', 'hasLandingPage',
        'hasCurator', 'hasHosting',
        'hasSubmissionDate', 'hasAcceptedDate',
        'hasTransferDate', 'hasTransferMethod',
        'hasUpdateDate'
    );
    
    private function isCustomClass(string $type): string
    {
        if (in_array($type, self::$actors_involved)) {
            return 'actors_involved';
        }
        if (in_array($type, self::$coverage)) {
            return 'coverage';
        }
        if (in_array($type, self::$right_access)) {
            return 'right_access';
        }
        if (in_array($type, self::$dates)) {
            return 'dates';
        }
        if (in_array($type, self::$relations_other_projects)) {
            return 'relations_other_projects';
        }
        if (in_array($type, self::$curation)) {
            return 'curation';
        }
        return'basic';
    }
    
    public function getData(array $data, string $lang = 'en')
    {
        $this->siteLang = $lang;
        $this->data = $data;
        $this->setupMetadataGuiType();
        return $this->result;
    }
    /**
     * Create the reponse header
     * @param array $data
     */
    private function setupMetadataGuiType()
    {
        $this->result['$schema'] = "http://json-schema.org/draft-07/schema#";
        $this->formatMetadataGuiView();
    }
    
    /**
     * Format the metadata gui result for the json output
     */
    private function formatMetadataGuiView()
    {
        
        //key => collection/project/resource
        foreach ($this->data as $key => $values) {
            foreach ($values as $k => $v) {
                $tableClass = 'basic';
                if (!isset($v->label)) {
                    break;
                } else {
                    //check the properties for the custom gui table section
                    $tableClass = $this->isCustomClass(str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property));
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['machine_name'] = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
                    //setup the default values
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['minCardinality'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['maxCardinality'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['recommendedClass'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = '-';
                }
                
                if (isset($v->property)) {
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['property'] = $v->label[$this->siteLang];
                }
                if (isset($v->order)) {
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['ordering'] = $v->order;
                }
                if (isset($v->min)) {
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['minCardinality'] = $v->min;
                }

                if (isset($v->max)) {
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['maxCardinality'] = $v->max;
                }
                
                if (isset($v->recommended) && $v->recommended === true) {
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['recommendedClass'] = '1';
                }
                
                $this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = $this->metadataGuiCardinality($v);
                //$this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = $this->metadataGuiCardinalityByMartina($v);
            }
        }
        $this->result['properties']['basic'] = $this->reorderPropertiesByOrderValue($this->result['properties']['basic']);
        $this->result['properties']['relations_other_projects'] = $this->reorderPropertiesByOrderValue($this->result['properties']['relations_other_projects']);
        $this->result['properties']['coverage'] = $this->reorderPropertiesByOrderValue($this->result['properties']['coverage']);
        $this->result['properties']['actors_involved'] = $this->reorderPropertiesByOrderValue($this->result['properties']['actors_involved']);
        $this->result['properties']['curation'] = $this->reorderPropertiesByOrderValue($this->result['properties']['curation']);
        $this->result['properties']['dates'] = $this->reorderPropertiesByOrderValue($this->result['properties']['dates']);
        $this->result['properties']['right_access'] = $this->reorderPropertiesByOrderValue($this->result['properties']['right_access']);
    }
    
    /**
     * Reorder the elements based on the ordering value
     * @param array $data
     * @return array
     */
    private function reorderPropertiesByOrderValue(array $data): array
    {
        $result = array();
        foreach ($data as $k => $v) {
            $result[$v['basic_info']['ordering']][$k] = $v;
        }
        return $result;
    }
    
    /**
     * "optional" means "$min empty or equal to 0"
     * "mandatory" is "$min greater than 0 and $recommended not equal true"
     * "recommended" is "$min greater than 0 and $recommended equal to true"
     *
     * @param object $data
     * @return string
     */
    private function metadataGuiCardinality(object $data): string
    {
        $val = '-';
        if ($data->min == 0 || empty($data->min)) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1 || !isset($data->max)) {
                $val = 'o*';
            } else {
                //optional
                $val = 'o';
            }
        }
          
        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) && $data->recommended !== true) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1 || !isset($data->max)) {
                $val =  'm*';
            } else {
                //mandatory
                $val =  'm';
            }
            return $val;
        }
          
        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) || $data->recommended === true) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1 || !isset($data->max)) {
                $val =  'r*';
            } else {
                //recommended
                $val =  'r';
            }
        }
        
        return $val;
    }
    /*
    - if we have minCardinality and minCardinality >=1 => m
    - if we have minCardinality and it is 0 or empty => o
    - if we have recommendedClass and it is not empty => r
    - if we have cardinality and it is 1 => m
    - if we have (maxCardinality and it is empty) and (if we don't have cardinality (cardinality not = 1) => *
    */
    private function metadataGuiCardinalityByMartina(object $data): string
    {
        $cardinality = '';
        //- if we have minCardinality and minCardinality >=1 => m
        //if we have cardinality and it is 1 => m
        if (isset($data->min) && $data->min >= 1) {
            $cardinality = 'm';
        }
        //if we have minCardinality and it is 0 or empty => o
        if (isset($data->min) || $data->min == 0) {
            $cardinality = 'o';
        }
        //if we have recommendedClass and it is not empty => r
        if (isset($data->recommended) && $data->recommended === true) {
            $cardinality = 'r';
        }
                
        //if we have (maxCardinality and it is empty) and (if we don't have cardinality (cardinality not = 1) => *
        if ((isset($data->max) && $data->max > 1) || (isset($data->max) && empty($data->max))) {
            $cardinality .= '*';
        }
        
        return $cardinality;
    }
    
    
    /**
     * Get the root table data
     *
     * @param array $data
     * @param string $lang
     * @return string
     */
    public function getRootTable(array $data, string $lang = 'en'): string
    {
        $this->siteLang = $lang;
        $this->reorderRt($data);
        return $this->createRootTableHtml();
    }
    
    private function createRootTableHtml(): string
    {
        $html = '';
        if (count($this->data)) {
            // Open the table
            $html .= "<table border='1'>";
            $html .= '<tr>';
            $html .= '<td>Property</td>';
            $html .= '<td>Project</td>';
            $html .= '<td>Collection</td>';
            $html .= '<td>Resource</td>';
            $html .= '<td>Metadata</td>';
            $html .= '<td>Image</td>';
            $html .= '<td>Publication</td>';
            $html .= '<td>Place</td>';
            $html .= '<td>Organisation</td>';
            $html .= '<td>Person</td>';
            $html .= '<td>Order</td>';
            $html .= '<td>domain</td>';
            $html .= '<td>Range</td>';
            $html .= '<td>Vocabulary</td>';
            $html .= '<td>Recommended Class</td>';
            $html .= '<td>LangTag</td>';
            $html .= '</tr>';
            // Cycle through the array
            foreach ($this->data as $type) {
                $html .= '<tr>';
                if (isset($type['main']['title'])) {
                    $html .= '<td>'.$type['main']['title'].'</td>';
                } else {
                    $html .= '<td>TITLE MISSING</td>';
                }
                    
                if (isset($type['project']['value'])) {
                    $html .= '<td>'.$type['project']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['collection']['value'])) {
                    $html .= '<td>'.$type['collection']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['resource']['value'])) {
                    $html .= '<td>'.$type['resource']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['metadata']['value'])) {
                    $html .= '<td>'.$type['metadata']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['image']['value'])) {
                    $html .= '<td>'.$type['image']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['publication']['value'])) {
                    $html .= '<td>'.$type['publication']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['place']['value'])) {
                    $html .= '<td>'.$type['place']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['organisation']['value'])) {
                    $html .= '<td>'.$type['organisation']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                    
                if (isset($type['person']['value'])) {
                    $html .= '<td>'.$type['person']['value'].'</td>';
                } else {
                    $html .= '<td>x</td>';
                }
                if (isset($type['main']['order'])) {
                    $html .= '<td>'.$type['main']['order'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                if (isset($type['main']['domain'])) {
                    $html .= '<td>'.$type['main']['domain'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                if (isset($type['main']['range'])) {
                    $html .= '<td>'.$type['main']['range'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                if (isset($type['main']['vocabs'])) {
                    $html .= '<td>'.$type['main']['vocabs'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                if (isset($type['main']['recommended'])) {
                    $html .= '<td>'.$type['main']['recommended'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                if (isset($type['main']['langTag'])) {
                    $html .= '<td>'.$type['main']['langTag'].'</td>';
                } else {
                    $html .= '<td></td>';
                }
                $html .= '</tr>';
            }
            $html .= "</table>";
        }
        
        return $html;
    }
    
    /**
     * Create the cardinality for the roottable
     *
     * @param string $min
     * @param string $max
     * @return string
     */
    private function rtCardinality(string $min = null, string $max = null): string
    {
        if (is_null($min) && is_null($max)) {
            return '0-n';
        }
        
        if (((int)$min >= 1) && ((!(int)$max) || (int)$max > 1)) {
            return '1-n';
        }
        
        if ((is_null($min)) && ((int)$max >= 1)) {
            return '0-1';
        }
        return '_';
    }
    
    /**
     * Reorder the root table result
     *
     * @param array $data
     */
    private function reorderRt(array $data)
    {
        foreach ($data as $kt => $kv) {
            foreach ($kv as $v) {
                if (isset($v->order)) {
                    if (isset($v->label['en'])) {
                        $this->data[$v->order]['main']['title'] = $v->label['en'];
                        $this->data[$v->order][$kt]['title'] = $v->label['en'];
                    }
                    if (isset($v->min) || isset($v->max)) {
                        $this->data[$v->order][$kt]['value'] = $this->rtCardinality($v->min, $v->max);
                    } elseif ((is_null($v->min) && is_null($v->max))) {
                        $this->data[$v->order][$kt]['value'] = '0-n';
                    }
                    if (isset($v->label['en'])) {
                        $this->data[$v->order][$kt]['title'] = $v->label['en'];
                    }
                    $this->data[$v->order]['main']['domain'] = $v->domain;
                    $this->data[$v->order]['main']['min'] = $v->min;
                    $this->data[$v->order]['main']['max'] = $v->max;
                    if (isset($v->range)) {
                        $this->data[$v->order]['main']['range'] = $v->range;
                    }

                    if (isset($v->vocabs)) {
                        $this->data[$v->order]['main']['vocabs'] = $v->vocabs;
                    }

                    if (isset($v->recommended)) {
                        $this->data[$v->order]['main']['recommended'] = $v->recommended;
                    }
                    $this->data[$v->order]['main']['order'] = $v->order;
                    if (isset($v->langTag)) {
                        $this->data[$v->order]['main']['langTag'] = $v->langTag;
                    }
                }
            }
            ksort($this->data);
        }
    }
}
