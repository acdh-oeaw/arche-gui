<?php

namespace Drupal\acdh_repo_gui\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\acdh_repo_gui\Model\BlocksModel;
use Drupal\acdh_repo_gui\Helper\FormHelper;

class ComplexSearchForm extends FormBase
{
    private $model;
    private $helper;
    private $entityData = array();
    private $yearsData = array();

    /**
     * Set up necessary properties
     */
    public function __construct()
    {
        $this->model = new BlocksModel();
        $this->helper = new FormHelper();
    }

    /**
     * Set up the form id
     * @return string
     */
    public function getFormId()
    {
        return "sks_form";
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

        $binarySearch["title"] = 'Search in payload?';
        $binarySearch["type"] = "payloadSearch";
        $binarySearch["fields"] = array('Yes' => 'Yes');
        $this->createBox($form, $binarySearch);

        //the entity box section
        $this->entityData = $this->model->getViewData("entity");
        if (count($this->entityData) > 0) {
            $this->entityData = $this->helper->formatEntityTypes($this->entityData);
            $resData["title"] = t('Type of Entity')->__toString();
            $resData["type"] = "searchbox_types";
            $resData["fields"] = $this->entityData['fields'];
            $this->createBox($form, $resData);
        }

        //the years box section
        $this->yearsData = $this->model->getViewData("years");
        if (count($this->yearsData) > 0) {
            $this->yearsData = $this->helper->formatEntityYears($this->yearsData, true);
            $dateData["title"] = t('Entities by Year')->__toString();
            $dateData["type"] = "datebox_years";
            $dateData["fields"] = $this->yearsData['fields'];
            $this->createBox($form, $dateData);
        }

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
        $metavalue = $form_state->getValue('metavalue');
        $types = $form_state->getValue('searchbox_types');
        if (count($types) > 0) {
            $types = array_filter($types);
        }

        $formats = $form_state->getValue('searchbox_format');
        if (count($formats) > 0) {
            $formats = array_filter($formats);
        }

        if ((empty($metavalue)) && (count($types) <= 0) && (count($formats) <= 0) && empty($form_state->getValue('date_start_date')) && empty($form_state->getValue('date_end_date'))) {
            $form_state->setErrorByName('metavalue', $this->t('Missing')->__toString() . ': ' . t('Keyword')->__toString() . ' ' . t('or')->__toString() . ' ' . t('Type')->__toString());
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

        $metaVal = urlencode($metavalue);
        $form_state->setRedirect(
            'repo_complexsearch',
            [
                    "metavalue" => $metaVal,
                    "order" => "datedesc",
                    "limit" => "10",
                    "page" => "1"]
        );
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
            '#value' => t('Apply the selected search filters'),
            '#attributes' => array(
                'class' => array('complexsearch-btn')
            ),
            '#button_type' => 'primary',
        );
    }
}
