<?php

namespace Drupal\acdh_repo_gui\PathProcessor;

use Drupal\Core\PathProcessor\InboundPathProcessorInterface;
use Symfony\Component\HttpFoundation\Request;

class AcdhRepoGuiPathProcessor implements InboundPathProcessorInterface
{
    public function processInbound($path, Request $request)
    {
        if (strpos($path, '/oeaw_detail/') === 0) {
            $names = preg_replace('|^\/oeaw_detail\/|', '', $path);
            $names = str_replace('/', ':', $names);
            return "/oeaw_detail/$names";
        }
        
        if (strpos($path, '/api/vcr/') === 0) {
            $names = preg_replace('|^\/api/vcr\/|', '', $path);
            $names = str_replace('/', ':', $names);
            return "/api/vcr/$names";
        }
        
        if (strpos($path, '/api/search_vcr/') === 0) {
            $names = preg_replace('|^\/api/search_vcr\/|', '', $path);
            $names = str_replace('/', ':', $names);
            return "/api/search_vcr/$names";
        }
        
        if (strpos($path, '/search/') === 0) {
            $names = preg_replace('|^\/search\/|', '', $path);
            $names = str_replace('/', ':', $names);
            return "/search/$names";
        }
        
        return $path;
    }
}
