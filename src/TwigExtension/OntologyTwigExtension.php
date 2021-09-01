<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

use Drupal\acdh_repo_gui\Model\OntologyTwigExtensionModel;

/**
 * Description of OntologyTwigExtension
 *
 * @author nczirjak
 */
class OntologyTwigExtension extends \Twig_Extension
{
    private $model;
    private $str = "";
    
    /**
     * {@inheritdoc}
     * This function must return the name of the extension. It must be unique.
     */
    public function getName()
    {
        return 'acdh_repo_gui_ontology.twig_extension';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_acdh_ontology_import_date', [$this, 'get_acdh_ontology_import_date']),
        ];
    }

    /**
     * Get the latest owl file ingest date from the DB
     * @return type
     */
    public function get_acdh_ontology_import_date()
    {
        $this->createModel();
        $importDate = $this->model->getViewData();
        
        if (isset($importDate['value'])) {
            $date = new \DateTime($importDate['value']);
            $this->str = "Ontology import date: ".$date->format('Y-m-d H:i:s');
        }
         
        return $this->str;
    }
    
    private function createModel()
    {
        $this->model = new \Drupal\acdh_repo_gui\Model\OntologyTwigExtensionModel();
    }
}
