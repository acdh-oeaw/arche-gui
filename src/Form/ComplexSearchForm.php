<?php

namespace Drupal\acdh_repo_gui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\acdh_repo_gui\Model\BlocksModel;
use Drupal\acdh_repo_gui\Helper\FormHelper;


class ComplexSearchForm extends FormBase
{
    private $langConf;
    private $model;
    private $helper;
    private $entityData = array();
    private $yearsData = array();
    /**
     * Set up necessary properties
     */
    public function __construct()
    {
        $this->langConf = $this->config('arche.settings');
        $this->model = new BlocksModel();
        $this->helper = new FormHelper();
    }
    
    /**
     * Set up the form id
     * @return string
     */
    public function getFormId()
    {
        return "repo_search_form";
    }
    
    /**
     * Build form
     * @param array $form
     * @param FormStateInterface $form_state
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        
        //the input field
        $this->createSearchInput($form);
        
        //the entity box section
        $this->entityData = $this->model->getViewData("entity");
        if(count($this->entityData) > 0) {
            $this->entityData = $this->helper->formatEntityYears($this->entityData);
            $resData["title"] = $this->langConf->get('gui_type_of_entity') ? $this->langConf->get('gui_type_of_entity') : 'Type of Entity' ;
            $resData["type"] = "searchbox_types";
            $resData["fields"] = $this->entityData['fields'];
            $this->createBox($form, $resData);
            
        }
        
        //the years box section
        $this->yearsData = $this->model->getViewData("years");
        if(count($this->yearsData) > 0) {
            $this->yearsData = $this->helper->formatEntityYears($this->yearsData, true);
            $dateData["title"] = $this->langConf->get('gui_entities_by_year') ? $this->langConf->get('gui_entities_by_year') :  'Entities by Year';
            $dateData["type"] = "datebox_years";
            $dateData["fields"] = $this->yearsData['fields'];
            $this->createBox($form, $dateData);
            
        }
        
        
        /****  Entities By date *****/
        /*
        $entititesTitle = $this->langConf->get('gui_entities_by_date') ? $this->langConf->get('gui_entities_by_date') :  'Entities by Date';
        $form['datebox']['title'] = [
            '#markup' => '<h3 class="extra-filter-heading date-filter-heading">'.$entititesTitle.'</h3>'
        ];
        
        $form['datebox']['date_start_date'] = [
          '#type' => 'textfield',
          '#title' => $this->t('From'),
            '#attributes' => array(
                'class' => array('date-filter start-date-filter'),
                'placeholder' => t('dd/mm/yyyy'),
            )
        ];
        
        $form['datebox']['date_end_date'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Until'),
            '#attributes' => array(
                'class' => array('date-filter end-date-filter'),
                'placeholder' => t('dd/mm/yyyy'),
            )
        ];
        */
        return $form;
    }
    
   
    
    /**
     * Validate the form
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        error_log("validate form");
        $metavalue = $form_state->getValue('metavalue');
        error_log("metavalue");
        error_log(print_r($metavalue, true));
        
        $types = $form_state->getValue('searchbox_types');
        error_log("types");
        error_log(print_r($types, true));
        if (count($types) > 0) {
            $types = array_filter($types);
        }
        
        $formats = $form_state->getValue('searchbox_format');
        
        error_log("formats");
        error_log(print_r($formats, true));
        if (count($formats) > 0) {
            $formats = array_filter($formats);
        }
        
        if ((empty($metavalue)) && (count($types) <= 0)
                &&  (count($formats) <= 0)  && empty($form_state->getValue('date_start_date'))
                && empty($form_state->getValue('date_end_date'))) {
            $form_state->setErrorByName('metavalue', $this->t('Missing').': '.t('Keyword').' '.t('or').' '.t('Type'));
        }
        
        
        
            
    }
    
    /**
     * Form submit
     *
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        
        $metavalue = $form_state->getValue('metavalue');
        
        $extras = array();
        
        $types = $form_state->getValue('searchbox_types');
        $types = array_filter($types);
        
        $formats = $form_state->getValue('searchbox_format');
        $formats = array_filter($formats);
        
        $startDate = $form_state->getValue('date_start_date');
        $endDate = $form_state->getValue('date_end_date');
                
        if (count($types) > 0) {
            foreach ($types as $t) {
                $extras["type"][] = strtolower($t);
            }
        }
        
        if (count($formats) > 0) {
            foreach ($formats as $f) {
                $extras["formats"][] = strtolower($f);
            }
        }
        
        if (!empty($startDate) && !empty($endDate)) {
            $startDate = str_replace('/', '-', $startDate);
            $startDate = date("Ymd", strtotime($startDate));
            $endDate = str_replace('/', '-', $endDate);
            $endDate = date("Ymd", strtotime($endDate));
            $extras["start_date"] = $startDate;
            $extras["end_date"] = $endDate;
        }
        
        /*
        $metaVal = $this->oeawFunctions->convertSearchString($metavalue, $extras);
        $metaVal = urlencode($metaVal);
        $form_state->setRedirect('oeaw_complexsearch', ["metavalue" => $metaVal, "limit" => 10,  "page" => 1]);
         * 
         */
    }
    
    
     /**
     * Create the checkbox templates
     *
     * @param array $form
     * @param array $data
     */
    private function createBox(array &$form, array $data)
    {
        $form['search'][$data["type"]] = array(
            '#type' => 'checkboxes',
            '#title' => $this->t($data["title"]),
            '#attributes' => array(
                'class' => array('checkbox-custom', $data["type"]),
            ),
            '#options' =>
                $data["fields"]
        );
    }
    
    /**
     * this function creates the search input field
     *
     * @param array $form
     * @return array
     */
    private function createSearchInput(array &$form)
    {
        $form['metavalue'] = array(
            '#type' => 'textfield',
            '#attributes' => array(
                'class' => array('form-control')
            ),
            #'#required' => TRUE,
        );
        
        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = array(
            '#type' => 'submit',
            '#value' => $this->langConf->get('gui_apply_selected_filters') ? $this->langConf->get('gui_apply_selected_filters') : 'Apply the selected search filters',
            '#attributes' => array(
                'class' => array('complexsearch-btn')
            ),
            '#button_type' => 'primary',
        );
    }
}
