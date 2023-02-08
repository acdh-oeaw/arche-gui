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
    private $categoryData = array();
    private $lastModifyDateTime;
    private $reCache= false;
    private $searchStr = "";

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
        $form['#prefix'] = '<div class="sks-form">';
        //the input field
        $this->createSearchInput($form);

        $binarySearch["title"] = 'Search in payload?';
        $binarySearch["type"] = "payloadSearch";
        $binarySearch["fields"] = array('Yes' => 'Yes');
        $this->createBox($form, $binarySearch);
        $this->lastModifyDateTime = $this->getCacheLastModificationDate();
        //do we need to recache the data?
        $this->reCache = $this->helper->checkCacheData('entity', $this->lastModifyDateTime);
  
        //get the data based on the recache and type value
        $this->entityData = $this->getBoxData('entity');
        
        if (count((array)$this->entityData) > 0) {
            $this->entityData = $this->helper->formatEntityTypes($this->entityData);
            $resData["title"] = t('Type of Entity')->__toString();
            $resData["type"] = "searchbox_types";
            $resData["fields"] = $this->entityData['fields'];
            $this->createBox($form, $resData);
        }
        
        $this->categoryData = $this->getBoxData('category');
        if (count((array)$this->categoryData) > 0) {
            $this->categoryData = $this->helper->formatCategoryTypes($this->categoryData);
            $resData["title"] = t('Category')->__toString();
            $resData["type"] = "searchbox_category";
            $resData["fields"] = $this->categoryData['fields'];
            $this->createBox($form, $resData);
        }

        //the years box section
        $this->yearsData = $this->getBoxData('years');
        if (count((array)$this->yearsData) > 0) {
            $this->yearsData = $this->helper->formatEntityYears($this->yearsData, true);
            $dateData["title"] = t('Entities by Year')->__toString();
            $dateData["type"] = "datebox_years";
            $dateData["fields"] = $this->yearsData['fields'];
            $this->createBox($form, $dateData);
        }
      
        $this->addSubmitButton($form);
       
        $form['#suffix'] = '</div>';
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
        if (count((array)$types) > 0) {
            $types = array_filter($types);
        }

        $categories = $form_state->getValue('searchbox_category');
        if (count((array)$categories) > 0) {
            $categories = array_filter($categories);
        }
        
        $years = $form_state->getValue('datebox_years');
        if (count((array)$years) > 0) {
            $years = array_filter($years);
        }

        if ((empty($metavalue)) && (count((array)$types) <= 0) && (count((array)$categories) <= 0) && (count((array)$years) <= 0)) {
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
        $this->generateMetaUrlString($form_state);
        $form_state->setRedirect(
            'repo_search_v2',
            [
                    "metavalue" => $this->searchStr,
                    "order" => "datedesc",
                    "limit" => "10",
                    "page" => "1"]
        );
    }
    
    private function generateMetaUrlString(FormStateInterface &$form_state)
    {
        $this->addSearchText($form_state->getValue('metavalue'));
        $this->addSearchType($form_state->getValue('searchbox_types'));
        $this->addSearchCategory($form_state->getValue('searchbox_category'));
        $this->addSearchDate($form_state->getValue('datebox_years'));
        $this->addPayload($form_state->getValue('payloadSearch'));
    }
    
    private function addPayload($payload = "")
    {
        if (is_array($payload)) {
            $this->searchStr .= "&payload=false";
            foreach ($payload as $p) {
                if (strtolower($p) === "yes") {
                    $this->searchStr .= "&payload=true";
                }
            }
        }
    }
    
    private function addSearchText($text = "")
    {
        if (!empty($text)) {
            $this->searchStr .= "words=".str_replace(" ", "+", $text);
        }
    }
    
    private function addSearchType(mixed $types = "")
    {
        $types = array_filter($types);
        if (count((array)$types) > 0) {
            if (!empty($this->searchStr)) {
                $this->searchStr .= "&";
            }
            $this->searchStr .= "type=";
            $lastElement = end($types);
            foreach ($types as $t) {
                $this->searchStr .=$t;
                if ($t !== $lastElement) {
                    $this->searchStr .= '+';
                }
            }
        }
    }
    
    private function addSearchCategory(mixed $category = "")
    {
        $category = array_filter($category);
        if (count((array)$category) > 0) {
            if (!empty($this->searchStr)) {
                $this->searchStr .= "&";
            }
            $this->searchStr .= "category=";
            $lastElement = end($category);
            foreach ($category as $c) {
                $this->searchStr .=$c;
                if ($c !== $lastElement) {
                    $this->searchStr .= '+';
                }
            }
        }
    }
    
    private function addSearchDate(mixed $years)
    {
        $years = array_filter($years);
        if (count((array)$years) > 0) {
            if (!empty($this->searchStr)) {
                $this->searchStr .= "&";
            }
            $this->searchStr .= "years=";
            $lastElement = end($years);
            foreach ($years as $y) {
                $this->searchStr .= $y;
                if ($y !== $lastElement) {
                    $this->searchStr .= '+';
                }
            }
        }
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
                'id' => 'my-checkbox'
            ),
            '#options' =>
            $data["fields"],
            '#options_attributes' => array(
                
            )
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
    }
    
    private function addSubmitButton(array &$form)
    {
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

    private function getCacheLastModificationDate(): string
    {
        $data = $this->model->lastModificationDate();
        return (isset($data->max)) ? (string)$data->max : "";
    }

    private function getBoxData(string $type): array
    {
        //we need to get the DB
        if ($this->reCache) {
            $data = $this->model->getViewData($type);
            $time = strtotime($this->lastModifyDateTime);
            \Drupal::cache()->set('archeCacheSF_'.$type, $data, \Drupal\Core\Cache\CacheBackendInterface::CACHE_PERMANENT, array(date('Y-m-d H:i:s', $time)));
            return $data;
        } else {
            return (isset(\Drupal::cache()->get('archeCacheSF_'.$type)->data)) ? \Drupal::cache()->get('archeCacheSF_'.$type)->data : array();
        }
        return array();
    }
}
