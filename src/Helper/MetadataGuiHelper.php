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
    private static $actors_involved = array(
        'hasPrincipalInvestigator', 'hasContact',
        'hasCreator', 'hasAuthor',
        'hasEditor', 'hasContributor',
        'hasFunder', 'hasLicensor',
        'hasMetadataCreator', 'hasDigitisingAgent'
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
        'hasRestrictionRole', 'hasLicenseSummary',
        'hasAccessRestrictionSummary'
    );
    private static $dates = array(
        'hasDate', 'hasStartDate',
        'hasEndDate', 'hasCreatedDate',
        'hasCreatedStartDate', 'hasCreatedEndDate',
        'hasCollectedStartDate', 'hasCollectedEndDate',
        'hasCreatedStartDateOriginal', 'hasCreatedEndDateOriginal'
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
        'hasTitleImage', 'isTitleImageOf',
        'hasVersionInfo'
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

    /*
     * If we have multiple properties then we need to get the acdh schema one
     */

    private function checkDataProperty(array $prop): string
    {
        foreach ($prop as $v) {
            if (strpos($v, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false) {
                return str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v);
            }
        }
        return "";
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
                //filter out the duplications
                if (strpos($k, 'https://vocabs.acdh.oeaw.ac.at/schema#') === false) {
                    continue;
                }

                if (!isset($v->label) || !isset($v->property)) {
                    break;
                } elseif (isset($v->ordering) && ((int) $v->ordering !== 99999)) {

                    //check the properties for the custom gui table section
                    $prop = $this->checkPropertyValue($v);

                    $tableClass = $this->isCustomClass($prop);

                    if (!isset($v->label[$this->siteLang])) {
                        $v->label[$this->siteLang] = $prop;
                    }

                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['machine_name'] = $prop;

                    $this->createCardinalityFieldsDefaultValue($v, $tableClass, $key);

                    if (isset($v->property)) {
                        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['property'] = $v->label[$this->siteLang];
                    }
                    if (isset($v->ordering)) {
                        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['ordering'] = $v->ordering;
                    }
                    if (isset($v->min)) {
                        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['minCardinality'] = $v->min;
                    }

                    if (isset($v->max)) {
                        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['maxCardinality'] = $v->max;
                    }

                    if (isset($v->recommendedClass) && $v->recommendedClass === true) {
                        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['recommendedClass'] = '1';
                    }

                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = $this->metadataGuiCardinality($v);
                }
                //$this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = $this->metadataGuiCardinalityByMartina($v);
            }
        }

        $this->result['properties']['basic'] = ($this->result['properties']['basic'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['basic']) :
                array();

        $this->result['properties']['relations_other_projects'] = ($this->result['properties']['relations_other_projects'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['relations_other_projects']) :
                array();

        $this->result['properties']['coverage'] = ($this->result['properties']['coverage'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['coverage']) :
                array();

        $this->result['properties']['actors_involved'] = ($this->result['properties']['actors_involved'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['actors_involved']) :
                array();

        $this->result['properties']['curation'] = ($this->result['properties']['curation'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['curation']) :
                array();
        $this->result['properties']['dates'] = ($this->result['properties']['dates']) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['dates']) :
                array();

        $this->result['properties']['right_access'] = ($this->result['properties']['right_access'] != null) ?
                $this->reorderPropertiesByOrderValue($this->result['properties']['right_access']) :
                array();
    }

    /**
     * Check the property value
     * @param type $v
     * @return string
     */
    private function checkPropertyValue(object &$v): string
    {
        if (is_array($v->property)) {
            return $this->checkDataProperty($v->property);
        } else {
            return str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
        }
        return "";
    }

    /**
     * Set up the default values for the cardinalities
     * @param object $v
     * @param string $tableClass
     * @param string $key
     * @return void
     */
    private function createCardinalityFieldsDefaultValue(object &$v, string $tableClass, string $key): void
    {
        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['minCardinality'] = '-';
        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['maxCardinality'] = '-';
        $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['recommendedClass'] = '-';
        $this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = '-';
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
            if (isset($v['basic_info']['ordering'])) {
                $result[$v['basic_info']['ordering']][$k] = $v;
            }
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
            if ((isset($data->max) && $data->max > 1) || $data->min > 1 || !isset($data->max)) {
                $val = 'o*';
            } else {
                //optional
                $val = 'o';
            }
        }

        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) && $data->recommendedClass !== true) {
            if ((isset($data->max) && $data->max > 1) || $data->min > 1 || !isset($data->max)) {
                $val = 'm*';
            } else {
                //mandatory
                $val = 'm';
            }
            return $val;
        }

        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) || $data->recommendedClass === true) {
            if ((isset($data->max) && $data->max > 1) || $data->min > 1 || !isset($data->max)) {
                $val = 'r*';
            } else {
                //recommended
                $val = 'r';
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
     * The root table header html code
     * @return string
     */
    private function createRootTableHeader(): string
    {
        $html = "<style>
                table thead tr th {
                    position: sticky;
                    z-index: 100;
                    top: 0;
                }
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
                .sticky {position: sticky; z.index: 100; left: 0; background-color: #4CAF50; color:white;}
                </style>";
        $html .= "<table >";
        $html .= "<thead >";
        $html .= '<tr>';
        $html .= '<th><b>Property</b></th>';
        $html .= '<th><b>Project</b></th>';
        $html .= '<th><b>TopCollection</b></th>';
        $html .= '<th><b>Collection</b></th>';
        $html .= '<th><b>Resource</b></th>';
        $html .= '<th><b>Metadata</b></th>';
        $html .= '<th><b>Publication</b></th>';
        $html .= '<th><b>Place</b></th>';
        $html .= '<th><b>Organisation</b></th>';
        $html .= '<th><b>Person</b></th>';
        $html .= '<th><b>Order</b></th>';
        $html .= '<th><b>domain</b></th>';
        $html .= '<th><b>Range</b></th>';
        $html .= '<th><b>Vocabulary</b></th>';
        $html .= '<th><b>Recommended Class</b></th>';
        $html .= '<th><b>Automated Fill</b></th>';
        $html .= '<th><b>Default Value</b></th>';
        $html .= '<th><b>LangTag</b></th>';
        $html .= "</thead >";
        $html .= '</tr>';
            
        return $html;
    }
    
    
    private function createRootTableTd(array $value, string $key = null): string
    {
        if (isset($value[$key])) {
            return '<td>' . $value[$key] . '</td>';
        } else {
            return '<td></td>';
        }
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

            $html .= $this->createRootTableHeader();

            // Cycle through the array

            foreach ($this->data as $type) {
                $html .= '<tr>';

                if (isset($type['main']['title'])) {
                    $html .= '<td class="sticky"><b>' . $type['main']['title'] . '</b></td>';
                } else {
                    $html .= '<td class="sticky">TITLE MISSING</td>';
                }
                //create the type values
                $html .= $this->getRtTypeValues($type);

                $html .= $this->createRootTableTd($type['main'], 'order');
                $html .= '<td>' . $this->getRtTypeDomain($type) . '</td>';
                $html .= '<td>' . $this->getRtTypeRange($type) . '</td>';
                $html .= $this->createRootTableTd($type['main'], 'vocabs');
                $html .= '<td>' . $this->getRtTypeRecommended($type) . '</td>';
                
                $html .= $this->createRootTableTd($type['main'], 'automatedFill');
                $html .= $this->createRootTableTd($type['main'], 'defaultValue');
                $html .= $this->createRootTableTd($type['main'], 'langTag');
                
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
        $types = array('project', 'topcollection', 'collection', 'resource', 'metadata', 'publication', 'place', 'organisation', 'person');
        $html = '';
        foreach ($types as $t) {
            if (isset($type[$t]['value'])) {
                $html .= '<td>' . $type[$t]['value'] . '</td>';
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
        $types = array('project' => 'p', 'topcollection' => 'tc', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
        foreach ($types as $t => $v) {
            if (isset($type[$t]['domain'])) {
                $html .= '' . $v . ',';
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
        $types = array('project' => 'p', 'topcollection' => 'tc', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
        foreach ($types as $t => $v) {
            if (isset($t) && isset($type[$t]['recommended']) && $type[$t]['recommended'] == true) {
                $html .= '' . $v . ',';
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
        $types = array('project' => 'p', 'topcollection' => 'tc', 'collection' => 'c', 'resource' => 'r', 'metadata' => 'm', 'publication' => 'pub', 'place' => 'pl', 'organisation' => 'o', 'person' => 'pe');
        $html = '';
        $values = array();
        foreach ($types as $t => $v) {
            if (isset($type[$t]['range']) && count($type[$t]['range']) > 0) {
                foreach ($type[$t]['range'] as $r) {
                    if (strpos($r, '/api/') === false) {
                        $values[] = $r;
                    }
                }
            }
        }
        $values = array_unique($values);
        $html = implode(", ", $values);
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

        if (((int) $min >= 1) && ((!(int) $max) || (int) $max > 1)) {
            return '1-n';
        }

        if ((is_null($min)) && ((int) $max >= 1)) {
            return '0-1';
        }

        if ((int) $min == 1 && (int) $max == 1) {
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
        $uris = array();
        foreach ($data as $kt => $kv) {
            $domain = '';
            $domain .= $kt . ' ';
            
            if (is_array($kv)) {
                foreach ($kv as $v) {
                    if (isset($v->ordering) && isset($v->uri)) {
                       
                        //handle the duplicated ids
                        if ($v->ordering == 99999) {
                            if (count($uris) == 0) {
                                $uris[$v->uri] = $v->ordering;
                            } elseif (count($uris) > 0) {
                                if (key_exists($v->uri, $uris)) {
                                    $v->ordering = (int)$uris[$v->uri];
                                } else {
                                    $uris[$v->uri] = (int)max(array_keys($this->data)) + 1;
                                    $v->ordering = (int)$uris[$v->uri];
                                }
                            }
                        }
                        
                        //if we have already an undefined value with id 99999 then we have to change the
                        //orderid, because we use the order to generate the table
                        
                        $this->createRootTablePropertyTitle($v, $kt);
                        $this->createRootTablePropertyMinMax($v, $kt);
                        
                        if (isset($v->domain)) {
                            $this->data[$v->ordering][$kt]['domain'] = $v->domain;
                        }
 
                        $this->getOntologyObjData($v, $kt, 'range', 'range');
                        $this->getOntologyObjData($v, $kt, 'vocabs', 'vocabs');
                        $this->getOntologyObjData($v, $kt, 'recommended', 'recommendedClass');
                        $this->getOntologyObjData($v, $kt, 'automatedFill', 'automatedFill');
                        $this->getOntologyObjData($v, $kt, 'defaultValue', 'defaultValue');
                        $this->data[$v->ordering]['main']['order'] = $v->ordering;
                        $this->getOntologyObjData($v, $kt, 'langTag', 'langTag');
                        $this->data[$v->ordering]['main']['domain'] = $domain;
                    }
                }
                ksort($this->data);
            }
        }
    }
    
    /**
     * Get the ontology property data based on the keys
     * @param object $v
     * @param string $kt
     * @param string $dKey
     * @param string $vKey
     * @return void
     */
    private function getOntologyObjData(object &$v, string &$kt, string $dKey, string $vKey): void
    {
        if (isset($v->$vKey)) {
            $this->data[$v->ordering]['main'][$dKey] = $v->$vKey;
            $this->data[$v->ordering][$kt][$dKey] = $v->$vKey;
        }
    }

    /**
     * Create the root table property title from the uri
     * @param object $v
     * @param string $kt
     * @return void
     */
    private function createRootTablePropertyTitle(object &$v, string &$kt): void
    {
        $this->data[$v->ordering]['main']['title'] = preg_replace('|^.*[/#]|', '', $v->uri);
        if (isset($v->label['en'])) {
            $this->data[$v->ordering][$kt]['title'] = $v->label['en'];
        } else {
            $this->data[$v->ordering][$kt]['title'] = preg_replace('|^.*[/#]|', '', $v->uri);
        }
    }

    /**
     * Create Min max values
     * @param object $v
     * @param string $kt
     * @return void
     */
    private function createRootTablePropertyMinMax(object &$v, string &$kt): void
    {
        if (isset($v->min) || isset($v->max)) {
            $this->data[$v->ordering][$kt]['value'] = $this->rtCardinality($v->min, $v->max);
            $this->data[$v->ordering]['main']['min'] = $v->min;
            $this->data[$v->ordering]['main']['max'] = $v->max;
            $this->data[$v->ordering][$kt]['min'] = $v->min;
            $this->data[$v->ordering][$kt]['max'] = $v->max;
        } elseif ((is_null($v->min) && is_null($v->max))) {
            $this->data[$v->ordering][$kt]['value'] = '0-n';
        }
    }
}
