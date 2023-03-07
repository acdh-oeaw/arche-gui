<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

class ArcheJsonDecoderExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [ new \Twig_SimpleFilter('archeJsonDecoderFilter', function ($value) {
                return json_decode($value, true);
            })
        ];
    }

    /**
     * Gets a unique identifier for this Twig extension.
     */
    public function getName()
    {
        return 'acdh_repo_gui_json_decoder.twig_extension';
    }

   
}
