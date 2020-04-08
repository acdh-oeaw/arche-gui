<?php


namespace Drupal\acdh_repo_gui\Plugin\rest\resource;

use Drupal\rest\Plugin\ResourceBase;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use acdhOeaw\acdhRepoLib\Repo;
use acdhOeaw\acdhRepoLib\RepoResource;
use acdhOeaw\acdhRepoLib\RepoDb;

/**
 * Provides Metadata Table to GUI
 *
 * @RestResource(
 *   id = "api_getMetadataGui",
 *   label = @Translation("ARCHE Metadata to GUI provider"),
 *   uri_paths = {
 *     "canonical" = "/api/getMetadataGui/{lang}"
 *   }
 * )
 */
class ApiGetMetadataGuiResource extends ResourceBase
{
    private $config;
    private $repo;
    /*
     * Usage:
     *
     *  https://domain.com/browser/api/getData/{class}/{querystring}?_format=json
     */
    
    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui').'/config/config.yaml';
        $this->repo = Repo::factory($this->config);
    }
        
    /**
     * Responds to entity GET requests.
     *
     * @param string $class
     * @param string $searchStr
     * @param string $lang
     * @return Response|JsonResponse
     */
    public function get(string $class, string $searchStr)
    {
        $response = new Response();
        
        if (empty($class) || empty($searchStr)) {
            return new JsonResponse(array("Please provide a link"), 404, ['Content-Type'=> 'application/json']);
        }
        return new JsonResponse(array("Please provide a link"), 404, ['Content-Type'=> 'application/json']);
        
    }
}
