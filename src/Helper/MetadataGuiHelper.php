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
        $this->reorderRootTable($data);
       
        return $this->createRootTableHtml();
    }
    
    /**
     * Create the response html string
     * @return string
     */
    private function createRootTableHtml(): string
    {
        $html = '';
        
        if (count($this->data) > 0) {
            // Open the table

            $html .= "<style>
                table, tr, th, td {
                    border: 1px solid black;
                }
                tr, th, td {
                    padding: 15px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #4CAF50;
                    color: white;
                }
                tr:hover {background-color: #f5f5f5;}
                tr:nth-child(even) {background-color: #f2f2f2;}
                </style>";
            $html .= "<table >";
            $html .= '<tr>';
            $html .= '<th><b>Property</b></th>';
            $html .= '<th><b>Project</b></th>';
            $html .= '<th><b>Collection</b></th>';
            $html .= '<th><b>Resource</b></th>';
            $html .= '<th><b>Metadata</b></th>';
            $html .= '<th><b>Image</b></th>';
            $html .= '<th><b>Publication</b></th>';
            $html .= '<th><b>Place</b></th>';
            $html .= '<th><b>Organisation</b></th>';
            $html .= '<th><b>Person</b></th>';
            $html .= '<th><b>Order</b></th>';
            $html .= '<th><b>domain</b></th>';
            $html .= '<th><b>Range</b></th>';
            $html .= '<th><b>Vocabulary</b></th>';
            $html .= '<th><b>Recommended Class</b></th>';
            $html .= '<th><b>LangTag</b></th>';
            $html .= '</tr>';

            // Cycle through the array
                
            foreach ($this->data as $type) {
                $html .= '<tr>';
                
                if (isset($type['main']['title'])) {
                    $html .= '<td><b>'.$type['main']['title'].'</b></td>';
                } else {
                    $html .= '<td>TITLE MISSING</td>';
                }
                //create the type values
                $html .= $this->getRtTypeValues($type);

                if (isset($type['main']['order'])) {
                    $html .= '<td>'.$type['main']['order'].'</td>';
                } else {
                    $html .= '<td></td>';
                }

                $html .= '<td>'.$this->getRtTypeDomain($type).'</td>';
                
                $html .= '<td>'.$this->getRtTypeRange($type).'</td>';

                if (isset($type['main']['vocabs'])) {
                    $html .= '<td>'.$type['main']['vocabs'].'</td>';
                } else {
                    $html .= '<td></td>';
                }

                $html .= '<td>'.$this->getRtTypeRecommended($type).'</td>';

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
     * Create the HTML table acdh class values
     * @param array $type
     * @return string
     */
    private function getRtTypeValues(array $type): string
    {
        $types = array('project', 'collection', 'resource', 'metadata', 'image', 'publication', 'place', 'organisation', 'person');
        $html = '';
        foreach ($types as $t) {
            if (isset($type[$t]['value'])) {
                $html .= '<td>'.$type[$t]['value'].'</td>';
            } else {
                $html .= '<td>x</td>';
            }
        }
        return $html;
    }
    
    /**
     * Get and display the domain values from the ontology
     * @param array $type
     * @return string
     */
    private function getRtTypeDomain(array $type): string
    {
        $types = array('project' => 'p', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'image' => 'i', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
        foreach ($types as $t => $v) {
            if (isset($type[$t]['domain'])) {
                $html .= ''.$v.',';
            }
        }
        return $html;
    }
    
    /**
     * Get and display the recommended values from the ontology
     * @param array $type
     * @return string
     */
    private function getRtTypeRecommended(array $type): string
    {
        $types = array('project' => 'p', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'image' => 'i', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
        foreach ($types as $t => $v) {
            if (isset($type[$t]['recommended']) && $type[$t]['recommended'] == true) {
                $html .= ''.$v.',';
            }
        }
        return $html;
    }
    
    /**
     * Get and display the range values from the ontology
     * @param array $type
     * @return string
     */
    private function getRtTypeRange(array $type): string
    {
        $types = array('project' => 'p', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'image' => 'i', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
       
        foreach ($types as $t => $v) {
            
            if (isset($type[$t]['range']) && count($type[$t]['range']) > 0) {
                foreach($type[$t]['range'] as $r) {
                   
                    if (strpos($r, '/api/') === false) {
                        $html .= ''.$r.',';
                    } 
                }
            }
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
        
        if ((int)$min ==  1 && (int)$max == 1) {
            return '1';
        }
        return 'x';
    }
    
    /**
     * Reorder the root table result
     *
     * @param array $data
    */
    private function reorderRootTable(array $data)
    {
        foreach ($data as $kt => $kv) {
            $domain = '';
            $domain .= $kt.' ';
           
            foreach ($kv as $v) {
                if (isset($v->ordering)) {
                    
                    if (isset($v->uri)) {
                        $this->data[$v->ordering]['main']['title'] = preg_replace('|^.*[/#]|', '', $v->uri);
                        $this->data[$v->ordering][$kt]['title'] = preg_replace('|^.*[/#]|', '', $v->uri);
                    }
                    if (isset($v->min) || isset($v->max)) {
                        $this->data[$v->ordering][$kt]['value'] = $this->rtCardinality($v->min, $v->max); /*. '<br>_min: '.$v->min.'_ max: '.$v->max;*/
                    } elseif ((is_null($v->min) && is_null($v->max))) {
                        $this->data[$v->ordering][$kt]['value'] = '0-n'; /* <br> _ min and max null';*/
                    }
                    if (isset($v->label['en'])) {
                        $this->data[$v->ordering][$kt]['title'] = $v->label['en'];
                    }
                    if (isset($v->domain)) {
                        $this->data[$v->ordering][$kt]['domain'] = $v->domain;
                    }
                    
                    $this->data[$v->ordering]['main']['min'] = $v->min;
                    $this->data[$v->ordering]['main']['max'] = $v->max;

                    $this->data[$v->ordering][$kt]['min'] = $v->min;
                    $this->data[$v->ordering][$kt]['max'] = $v->max;
                    if (isset($v->range)) {
                        $this->data[$v->ordering]['main']['range'] = $v->range;
                        $this->data[$v->ordering][$kt]['range'] = $v->range;
                    }

                    if (isset($v->vocabs)) {
                        $this->data[$v->ordering]['main']['vocabs'] = $v->vocabs;
                        $this->data[$v->ordering][$kt]['vocabs'] = $v->vocabs;
                    }

                    if (isset($v->recommendedClass)) {
                        $this->data[$v->ordering]['main']['recommended'] = $v->recommendedClass;
                        $this->data[$v->ordering][$kt]['recommended'] = $v->recommendedClass;
                    }
                    $this->data[$v->ordering]['main']['order'] = $v->ordering;
                    
                    if (isset($v->langTag)) {
                        $this->data[$v->ordering]['main']['langTag'] = $v->langTag;
                        $this->data[$v->ordering][$kt]['langTag'] = $v->langTag;
                    }
                    
                    $this->data[$v->ordering]['main']['domain'] = $domain;
                }
            }
            
            ksort($this->data);
        }
    }
}
