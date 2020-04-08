<?php


namespace Drupal\acdh_repo_gui\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use Drupal\acdh_repo_gui\Model\ArcheApiModel;

/**
 * Provides an Places Checker Resource
 *
 * @RestResource(
 *   id = "api_places",
 *   label = @Translation("ARCHE Places Checker"),
 *   uri_paths = {
 *     "canonical" = "/api/places/{data}"
 *   }
 * )
 */
class ApiPlacesResource extends ResourceBase
{
    private $model;
    
    
    public function __construct() {
        $this->model = new ArcheApiModel();
    }
    /*
     * Usage:
     *
     *  https://domain.com/browser/api/places/MYVALUE?_format=json
     */
    
    
    /**
     * Responds to entity GET requests.
     * @param string $data
     * @return Response|JsonResponse
     */
    public function get(string $data)
    {
        $response = new Response();
        
        if (empty($data)) {
            return new JsonResponse(array("Please provide a link"), 404, ['Content-Type'=> 'application/json']);
        }
        
        $obj = new \stdClass();
        $obj->type = 'https://vocabs.acdh.oeaw.ac.at/schema#Place';
        $obj->searchStr = strtolower($data);
        
        $sql = $this->model->getViewData('Persons', $obj);
        
        $result = array();
        foreach($sql as $k => $v) {
            $result[$k]['uri'] = 'repourl'.$v->id;
            $result[$k]['title'] = $v->value;
        }
            
        
        $response->setContent(json_encode($result));
        $response->headers->set('Content-Type', 'application/json');
        
        return $response;
    }
}
