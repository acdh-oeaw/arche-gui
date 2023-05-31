<?php

namespace Drupal\acdh_repo_gui\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Drupal\acdh_repo_gui\Helper\PagingHelper;

/**
 * Description of SearchViewController
 *
 * @author nczirjak
 */
class SearchViewController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    
    
    public function displayLeftSideSearchBlock()
    {
        $myForm = $this->formBuilder()->getForm('Drupal\acdh_repo_gui\Form\ComplexSearchForm');
        $renderer = \Drupal::service('renderer');
        $myFormHtml = $renderer->renderRoot($myForm);
        $response['form_html'] = $myFormHtml;

        return new \Symfony\Component\HttpFoundation\JsonResponse($response);
        return [
            '#markup' => \Drupal\Core\Render\Markup::create("
                <h2>My Form is Below</h2>
                {$myFormHtml}
                <h2>My Form is Above</h2>
            ")
        ];
        
        //$form = \Drupal::formBuilder()->getForm('Drupal\acdh_repo_gui\Form\ComplexSearchForm');
        //return $form;
    }
    
    public function generateView(string $metavalue): array
    {
        

        return [
            '#theme' => 'arche-smart-search-result-view',
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-styles',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }
    
    /**
     * Get the json object with the search values for the VCR submit
     * @param string $metavalue
     * @return Response
     */
    public function search_vcr(string $metavalue): \Symfony\Component\HttpFoundation\Response
    {
        $this->modelData = $this->model->getVcr($this->helper->paramsToSqlParams($metavalue));
        if (count($this->modelData) > 0 && isset($this->modelData[0]->json_agg)) {
            return new \Symfony\Component\HttpFoundation\Response(\json_encode($this->modelData[0]->json_agg), 200, ['Content-Type' => 'application/json']);
        }
        return new Response(\json_encode(array("There is no data")), 404, ['Content-Type' => 'application/json']);
    }
}
