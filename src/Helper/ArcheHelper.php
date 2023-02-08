<?php

namespace Drupal\acdh_repo_gui\Helper;

/**
 * Description of ArcheHelper Static Class
 *
 * @author nczirjak
 */
class ArcheHelper
{
    private static $prefixesToChange = array(
        "http://fedora.info/definitions/v4/repository#" => "fedora",
        "http://www.ebu.ch/metadata/ontologies/ebucore/ebucore#" => "ebucore",
        "http://www.loc.gov/premis/rdf/v1#" => "premis",
        "http://www.jcp.org/jcr/nt/1.0#" => "nt",
        "http://www.w3.org/2000/01/rdf-schema#" => "rdfs",
        "http://www.w3.org/ns/ldp#" => "ldp",
        "http://www.iana.org/assignments/relation/" => "iana",
        "https://vocabs.acdh.oeaw.ac.at/schema#" => "acdh",
        "https://id.acdh.oeaw.ac.at/" => "acdhID",
        "http://purl.org/dc/elements/1.1/" => "dc",
        "http://purl.org/dc/terms/" => "dcterms",
        "http://www.w3.org/2002/07/owl#" => "owl",
        "http://xmlns.com/foaf/0.1/" => "foaf",
        "http://www.w3.org/1999/02/22-rdf-syntax-ns#" => "rdf",
        "http://www.w3.org/2004/02/skos/core#" => "skos",
        "http://hdl.handle.net/21.11115/" => "handle"
        //"http://xmlns.com/foaf/spec/" => "foaf"
    );
    
    
    /**
     * Create shortcut from the property for the gui
     *
     * @param string $prop
     * @return string
     */
    public static function createShortcut(string $prop): string
    {
        $prefix = array();
        
        if (strpos($prop, '#') !== false) {
            $prefix = explode('#', $prop);
            $property = end($prefix);
            $prefix = $prefix[0];
            if (isset(self::$prefixesToChange[$prefix.'#'])) {
                return self::$prefixesToChange[$prefix.'#'].':'.$property;
            }
        } else {
            $prefix = explode('/', $prop);
            $property = end($prefix);
            $pref = str_replace($property, '', $prop);
            if (isset(self::$prefixesToChange[$pref])) {
                return self::$prefixesToChange[$pref].':'.$property;
            }
        }
        return '';
    }
    
    public static function createFullPropertyFromShortcut(string $prop): string
    {
        $domain = self::getDomainFromShortCut($prop);
        $value = self::getValueFromShortCut($prop);
        if ($domain) {
            foreach (self::$prefixesToChange as $k => $v) {
                if ($v == $domain) {
                    return $k.$value;
                }
            }
        }
        return "";
    }
    
    private static function getDomainFromShortCut(string $prop): string
    {
        $prefix = explode(':', $prop);
        if (is_array($prefix)) {
            return $prefix[0];
        }
        return '';
    }
    
    private static function getValueFromShortCut(string $prop): string
    {
        $prefix = explode(':', $prop);
        if (is_array($prefix)) {
            return end($prefix);
        }
        return '';
    }
}
