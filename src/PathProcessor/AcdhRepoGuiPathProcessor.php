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
        
        return $path;
    }
}
