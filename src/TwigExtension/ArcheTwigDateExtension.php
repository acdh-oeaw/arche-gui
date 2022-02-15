<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

class ArcheTwigDateExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [ new \Twig_SimpleFilter('archeTranslateDateFilter', function ($value, $dateformat) {
            (isset($_SESSION['language'])) ? $lang = strtolower($_SESSION['language'])  : $lang = "en";
            
            if ($this->checkYearIsMoreThanFourDigit($value)) {
                return $this->notNormalDate($value, $lang, $dateformat);
            }
            return $this->returnFormattedDate($this->extendDateFormat($dateformat), $value, $lang);
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

    private function checkYearIsMoreThanFourDigit($value): bool
    {
        $explode = explode("-", $value);
        if (strlen($explode[0]) > 4) {
            return true;
        }
        return false;
    }

    /**
     * Return the normal 4 digit year dates
     * @param type $dateformat
     * @param type $value
     * @param type $lang
     * @return string
     */
    private function returnFormattedDate($dateformat, $value, $lang): string
    {
        if ($lang == 'de') {
            setlocale(LC_TIME, 'de_DE.utf8');
            return strftime($dateformat, strtotime($value));
        }
        
        setlocale(LC_TIME, 'en_US.utf8');
        return strftime($dateformat, strtotime($value));
    }

    
    /**
     * Return the befrore christ dates where we have 5 digit years numbers
     * @param type $value
     * @return string
     */
    private function notNormalDate($value, $lang, $dateformat): string
    {
        setlocale(LC_TIME, 'en_US.utf8');
        
        if ($lang == 'de') {
            setlocale(LC_TIME, 'de_DE.utf8');
        }
        $e = explode("-", $value);
        
        $y = $e[0];
        $m = $e[1];
        $d = $e[2];
        
        switch ($dateformat) {
            case 'Y':
                return $y;
            case 'd-m-Y':
                return $m.'-'.$d.'-'.$y;
            case 'd M Y':
                return $d.'-'.date('M', $m).'-'.$y;
            case 'Y M d':
                return $y.'-'.date('M', $m).'-'.$d;
            default:
                return $y.'-'.$m.'-'.$d;
        }
    }
}
