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
                    $tableClass = $this->isCustomClass(str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property));
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['basic_info']['machine_name'] = str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->property);
                    //setup the default values
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['minCardinality'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['maxCardinality'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]]['cardinalities'][$key]['recommendedClass'] = '-';
                    $this->result['properties'][$tableClass][$v->label[$this->siteLang]][$key] = '-';
                }
                //property => skos:altLabel
                //machine_name => acdh:hasTitle
              
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
        asort($this->result['properties']['basic']);
        asort($this->result['properties']['relations_other_projects']);
        asort($this->result['properties']['coverage']);
        asort($this->result['properties']['actors_involved']);
        asort($this->result['properties']['curation']);
        asort($this->result['properties']['dates']);
        asort($this->result['properties']['right_access']);
    }
    
    /**
     *
     * "optional" means "$min empty or equal to 0"
     * "mandatory" is "$min greater than 0 and $recommended not equal true"
     * "recommended" is "$min greater than 0 and $recommended equal to true"
     */
    private function metadataGuiCardinality(object $data): string
    {
        if ($data->min == 0 || empty($data->min)) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1) {
                return 'o*';
            }
            //optional
            return 'o';
        }
          
        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) && $data->recommended !== true) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1) {
                return 'm*';
            }
            //mandatory
            return 'm';
        }
          
        if ((isset($data->min) && (!empty($data->min)) && $data->min > 0) && $data->recommended === true) {
            if ((isset($data->max) && $data->max > 1)|| $data->min > 1) {
                return 'r*';
            }
            //recommended
            return 'r';
        }
        
        return '-';
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
}
