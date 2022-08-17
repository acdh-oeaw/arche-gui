<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

class ArcheTwigDateExtension extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [ new \Twig_SimpleFilter('archeTranslateDateFilter', function ($value, $dateformat) {
            (isset($_SESSION['language'])) ? $lang = strtolower($_SESSION['language'])  : $lang = "en";
            
            if (strpos($value, "Z") !== false) {
                $value = str_replace("Z", "", $value);
            }
            if ($this->checkYearIsMoreThanFourDigit($value)) {
                return $this->notNormalDate($value, $lang, $dateformat);
            }
            return $this->returnFormattedDate($dateformat, $value, $lang);
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

    private function checkYearIsMoreThanFourDigit($value): bool
    {
        $explode = explode("-", $value);
        if (strlen($explode[0]) > 4) {
            return true;
        }
        return false;
    }
    
    private function checkDateTimeValue($value) {
        $datetime = new \DateTime();
        $newDate = $datetime->createFromFormat('Y-m-d', $value);
        if($newDate) {
            return $newDate->format('Y-m-d H:i:s');
        }
        return $value;
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
        /* we have to setup the timezone and locale, otherwise it will mix up the year */
        ini_set('date.timezone', 'UTC');
        ini_set('intl.default_locale', 'de_DE');
        setlocale(LC_TIME, 'de_DE.utf8');
        
        $value = $this->checkDateTimeValue($value);
        $cal = \IntlCalendar::fromDateTime($value);
        
        if ($cal === null) {
            return "";
        }
        
        if ($lang == 'de') {
            return \IntlDateFormatter::formatObject($cal, $dateformat, 'de_DE');
        }
        ini_set('intl.default_locale', 'en_US');
        setlocale(LC_TIME, 'en_US.utf8');
        return \IntlDateFormatter::formatObject($cal, $dateformat, 'en_US');
    }

    
    /**
     * Return the befrore christ dates where we have 5 digit years numbers
     * @param type $value
     * @return string
     */
    private function notNormalDate($value, $lang, $dateformat): string
    {
        ini_set('date.timezone', 'UTC');
        ini_set('intl.default_locale', 'en_US');
        setlocale(LC_TIME, 'en_US.utf8');
        
        if ($lang == 'de') {
            ini_set('intl.default_locale', 'de_DE');
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
            case 'd-m-y':
                return $m.'-'.$d.'-'.$y;    
            case 'd M Y':
                return $d.'-'.date('M', $m).'-'.$y;
            case 'd M y':
                return $d.'-'.date('M', $m).'-'.$y;    
            case 'Y M d':
                return $y.'-'.date('M', $m).'-'.$d;
            case 'y M d':
                return $y.'-'.date('M', $m).'-'.$d;    
            default:
                return $y.'-'.$m.'-'.$d;
        }
    }
}
