<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

class ArcheTwigDateExtension extends \Twig\Extension\AbstractExtension 
{
    public function getFilters()
    {
        return [ new \Twig_SimpleFilter('archeTranslateDateFilter', function ($value, $dateformat) {
            (isset($_SESSION['language'])) ? $lang = strtolower($_SESSION['language'])  : $lang = "en";
                
            $dateformat = $this->extendDateFormat($dateformat);
                
            if ($lang == 'de') {
                setlocale(LC_TIME, 'de_DE.utf8');
                return strftime($dateformat, strtotime($value));
            } else {
                setlocale(LC_TIME, 'en_US.utf8');
                return strftime($dateformat, strtotime($value));
            }
        })
        ];
    }

    /**
     * Gets a unique identifier for this Twig extension.
     */
    public function getName()
    {
        return 'acdh_repo_gui.twig_extension';
    }

    /**
     * change the datefomat to work with strftime
     *
     * @param string $dateformat
     * @return string
     */
    private function extendDateFormat(string $dateFormat): string
    {
        $search  = array('Y', 'y', 'M', 'm', 'D', 'd');
        $replace = array('%Y', '%y', '%b', '%m', '%d', '%d');

        return str_replace($search, $replace, $dateFormat);
    }
}
