<?php

namespace Drupal\acdh_repo_gui\Object;

use Drupal\acdh_repo_gui\Helper\ArcheHelper as Helper;

class ResourceObject
{
    private $config;
    private $properties;
    private $acdhid;
    private $repoid;
    private $language = 'en';
    private $thumbUrl = 'https://arche-thumbnails.acdh.oeaw.ac.at/';
    private $biblatexUrl = 'https://arche-biblatex.acdh.oeaw.ac.at/';
    private $audioCategories = array('Audio', 'Sound', 'SpeechRecording');
    private $publicAccessValue = 'https://vocabs.acdh.oeaw.ac.at/archeaccessrestrictions/public';

    public function __construct(array $data, $config, string $language = 'en')
    {
        $this->properties = array();
        $this->config = $config;
        $this->language = $language;

        foreach ($data as $k => $v) {
            if (isset($v[$language])) {
                $this->setData($k, $v[$language]);
            } else {
                if (($language == 'en') && isset($v['de'])) {
                    $this->setData($k, $v['de']);
                } elseif (($language == 'de') && isset($v['en'])) {
                    $this->setData($k, $v['en']);
                } else {
                    $this->setData($k, reset($v));
                }
            }
        }

        //set acdhid /repoid / repourl
        $this->repoid = $this->getRepoID();
    }

    /**
     * Get the biblatex disserv url
     * @return string
     */
    public function getBiblatexUrl(): string
    {
        return $this->biblatexUrl . '?id=' . $this->getRepoUrl() . '&lang=' . $this->language;
    }

    /**
     * get the data based on the property
     *
     * @param string $property
     * @return array
     */
    public function getData(string $property): array
    {
        return (isset($this->properties[$property]) && !empty($this->properties[$property])) ? $this->properties[$property] : array();
    }

    /**
     *
     * Change property data
     *
     * @param string $prop
     * @param array $v
     */
    private function setData(string $prop = null, array $v = array())
    {
        if (
                isset($prop) && count((array) $v) > 0
        ) {
            $this->properties[$prop] = $v;
        }
    }

    /**
     * Get the Resource title
     *
     * @return string
     */
    public function getTitle(): string
    {
        if (isset($this->properties["acdh:hasTitle"][0]->title) && !empty($this->properties["acdh:hasTitle"][0]->title)) {
            return $this->properties["acdh:hasTitle"][0]->title;
        }

        if (isset($this->properties["acdh:hasTitle"][0]->value) && !empty($this->properties["acdh:hasTitle"][0]->value)) {
            return $this->properties["acdh:hasTitle"][0]->value;
        }

        return "";
    }

    /**
     * All identifiers
     *
     * @return array
     */
    public function getIdentifiers(): array
    {
        return (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) ? $this->properties["acdh:hasIdentifier"] : array();
    }

    /**
     * Get all identifiers which are not acdh related
     *
     * @return type
     */
    public function getNonAcdhIdentifiers(): array
    {
        $result = array();
        if (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $k => $v) {
                //filter out the baseurl related identifiers and which contains the id.acdh
                if ((strpos($v->value, $this->config->getBaseUrl()) === false) &&
                        (strpos($v->value, 'https://id.acdh.oeaw.ac.at') === false)
                ) {
                    $result[] = $v;
                }
            }
        }
        return $result;
    }
    
    /**
     * Get all identifiers which are not acdh api related
     *
     * @return type
     */
    public function getNonAcdhApiIdentifiers(): array
    {
        $result = array();
        if (isset($this->properties["acdh:hasIdentifier"]) && !empty($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $k => $v) {
                //filter out the baseurl related identifiers
                if ((strpos($v->value, $this->config->getBaseUrl()) === false)) {
                    $result[] = $v;
                }
            }
        }
        return $result;
    }

    /**
     * PID
     *
     * @return string
     */
    public function getPid(): string
    {
        return (
                isset($this->properties["acdh:hasPid"][0]->value) && !empty($this->properties["acdh:hasPid"][0]->value) && (
                    (strpos($this->properties["acdh:hasPid"][0]->value, 'http://') !== false) ||
                (strpos($this->properties["acdh:hasPid"][0]->value, 'https://') !== false)
                )
                ) ? $this->properties["acdh:hasPid"][0]->value : "";
    }

    /**
     * Get resource inside uri
     *
     * @return string
     */
    public function getInsideUrl(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->acdhid) && !empty($v->acdhid)) {
                    return str_replace('https://', '', $v->acdhid);
                }
            }
        }
        return "";
    }

    /**
     * Get the available date in a specified format
     * @return string
     */
    public function getAvailableDate(): string
    {
        if (isset($this->properties["acdh:hasAvailableDate"])) {
            foreach ($this->properties["acdh:hasAvailableDate"] as $v) {
                if (isset($v->value)) {
                    $time = strtotime($v->value);
                    return date('d m Y', $time);
                }
            }
        }
        return "";
    }

    /**
     * Get the resource acdh uuid
     *
     * @return string
     */
    public function getUUID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->acdhid) && !empty($v->acdhid)) {
                    return $v->acdhid;
                }
            }
        }
        return "";
    }

    /**
     * Get the resource acdh id
     *
     * @return string
     */
    public function getAcdhID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (strpos($v->value, '/id.acdh.oeaw.ac.at/') !== false &&
                        strpos($v->value, '/id.acdh.oeaw.ac.at/cmdi/') === false) {
                    return $v->value;
                }
            }
        }
        return "";
    }

    /**
     * Get the full repo url with the identifier for the actual resource
     *
     * @return string
     */
    public function getRepoUrl(): string
    {
        if (!isset($this->repoid) && empty($this->repoid)) {
            $this->getRepoID();
        }
        return $this->config->getBaseUrl() . $this->repoid;
    }

    /**
     * Get the Gui related url for the resource
     * @return string
     */
    public function getRepoGuiUrl(): string
    {
        if (!isset($this->repoid) && empty($this->repoid)) {
            $this->getRepoID();
        }
        return str_replace('/api/', '/browser/oeaw_detail/', $this->config->getBaseUrl()) . $this->repoid;
    }

    /**
     * Get the repo identifier
     * @return string
     */
    public function getRepoID(): string
    {
        if (isset($this->properties["acdh:hasIdentifier"])) {
            foreach ($this->properties["acdh:hasIdentifier"] as $v) {
                if (isset($v->id) && !empty($v->id)) {
                    $this->repoid = $v->id;
                    return $v->id;
                } else {
                    if (strpos($v->value, $this->config->getBaseUrl()) !== false) {
                        $this->repoid = str_replace($this->config->getBaseUrl(), '', $v->value);
                        return str_replace($this->config->getBaseUrl(), '', $v->value);
                    }
                }
            }
        }
        return "";
    }

    /**
     * Get the accessrestriction url and title
     *
     * @return array
     */
    public function getAccessRestriction(): array
    {
        $result = array();
        if (isset($this->properties["acdh:hasAccessRestriction"])) {
            foreach ($this->properties["acdh:hasAccessRestriction"] as $v) {
                if (isset($v->title) && !empty($v->title)) {
                    $result['title'] = $v->title;
                } elseif (isset($v->value) && !empty($v->value)) {
                    $result['title'] = $v->value;
                }

                if (isset($v->accessrestriction) && !empty($v->accessrestriction)) {
                    $result['uri'] = $v->accessrestriction;
                }
                if (isset($v->vocabsid) && !empty($v->vocabsid)) {
                    $result['vocabsid'] = $v->vocabsid;
                }
            }
        }
        return $result;
    }

    /**
     * get the title image url
     *
     * @return string
     */
    public function getTitleImage(string $width = '200px'): string
    {
        $img = '';
        $width = str_replace('px', '', $width);
        //check the thumbnail service first
        if ($this->getAcdhID()) {
            $acdhid = str_replace('https://', '', str_replace('http://', '', $this->getAcdhID()));
            if ($file = @fopen($this->thumbUrl . $acdhid, "r")) {
                $type = fgets($file, 40);
                if (!empty($type)) {
                    $img = $this->thumbUrl . $acdhid . '?width=' . $width;
                    return '<img src="' . $img . '" class="img-responsive">';
                }
            }
        }
        return '';
    }

    /**
     * Get the titleimage URL
     * @param string $width
     * @return string
     */
    public function getTitleImageUrl(string $width = '200px'): string
    {
        $img = '';
        $imgBinary = '';
        $width = str_replace('px', '', $width);
        //check the thumbnail service first
        if ($acdhid = $this->getAcdhID()) {
            $acdhid = str_replace('http://', '', $acdhid);
            $acdhid = str_replace('https://', '', $acdhid);
            if ($file = @fopen($this->thumbUrl . $acdhid, "r")) {
                $type = fgets($file, 40);
                if (!empty($type)) {
                    return $this->thumbUrl . $acdhid . '?width=' . $width;
                }
            }
        }
        return '';
    }

    /**
     * Check if we have a titleimage id or not
     * @return bool
     */
    public function isTitleImage(): bool
    {
        if (!empty($this->getAcdhID())) {
            return true;
        }
        return false;
    }

    /**
     * Get the acdh type string
     *
     * @return string
     */
    public function getAcdhType(): string
    {
        if (isset($this->properties["rdf:type"])) {
            foreach ($this->properties["rdf:type"] as $v) {
                if (isset($v->title) && !empty($v->title) && (strpos($v->title, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false)) {
                    return str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->title);
                } elseif (isset($v->value) && !empty($v->value) && (strpos($v->value, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false)) {
                    return str_replace('https://vocabs.acdh.oeaw.ac.at/schema#', '', $v->value);
                }
            }
        }
        return "";
    }

    /**
     * Display all RDF:Type Values
     * @return array
     */
    public function getRdfTypes(): array
    {
        $result = array();
        if (isset($this->properties["rdf:type"])) {
            foreach ($this->properties["rdf:type"] as $v) {
                if (isset($v->title) && !empty($v->title) && (strpos($v->title, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false)) {
                    $result[] = Helper::createShortcut($v->title);
                } elseif (isset($v->value) && !empty($v->value) && (strpos($v->value, 'https://vocabs.acdh.oeaw.ac.at/schema#') !== false)) {
                    $result[] = Helper::createShortcut($v->value);
                }
            }
        }
        return $result;
    }

    /**
     * Get the skos concept type for the custom gui detail view
     *
     * @return string
     */
    public function getSkosType(): string
    {
        if (isset($this->properties["rdf:type"])) {
            foreach ($this->properties["rdf:type"] as $v) {
                if (isset($v->title) && !empty($v->title) && (strpos($v->title, 'http://www.w3.org/2004/02/skos/core#') !== false)) {
                    return str_replace('http://www.w3.org/2004/02/skos/core#', '', $v->title);
                } elseif (isset($v->title) && !empty($v->value) && (strpos($v->value, 'http://www.w3.org/2004/02/skos/core#') !== false)) {
                    return str_replace('http://www.w3.org/2004/02/skos/core#', '', $v->value);
                }
            }
        }
        return "";
    }

    /**
     * Get all data
     *
     * @return array
     */
    public function getExpertTableData(): array
    {
        return $this->properties;
    }

    /**
     * Format the date values for the twig template
     *
     * @param string $property
     * @param string $dateFormat
     * @return string
     */
    public function getFormattedDateByProperty(string $property, string $dateFormat = 'Y'): string
    {
        if (isset($this->properties[$property])) {
            if (isset($this->properties[$property][0]->value)) {
                $val = strtotime($this->properties[$property][0]->value);
                return date($dateFormat, $val);
            }
        }
        return '';
    }

    /**
     * Select the identifier for the Copy resource link
     * Order : PID , ID.acdh.oeaw.ac.at, arche api id
     * REDMINE ID: #19888
     * @return string
     */
    public function getCopyResourceLink(): string
    {
        if (!empty($this->getPid())) {
            return $this->getPid();
        }
        
        if (!empty($this->getAcdhID())) {
            return $this->getAcdhID();
        }
        if (!empty($this->getRepoUrl())) {
            return $this->getRepoUrl();
        }
        
        return "";
    }

    /**
     * Create the JS string for the leaflet map MultiPolyLang from Multipolygon data
     * @return string
     */
    public function getMultiPolygonFirstCoordinate(): string
    {
        $str = "";
        if (isset($this->properties["acdh:hasWKT"][0]->title) && !empty($this->properties["acdh:hasWKT"][0]->title)) {
            $data = array_filter(explode(" ", $this->checkMultiPolygonMapString()));
            $first_coordinate = array_slice($data, 0, 2);
            $str = "[" . $first_coordinate[1] . " " . $first_coordinate[0] . "]";
        }
        return $str;
    }

    /**
     * Create the JS string for the leaflet map MultiPolyLang from Polygon data
     * @return string
     */
    public function getPolygonFirstCoordinate(): string
    {
        $str = "";
        if (isset($this->properties["acdh:hasWKT"][0]->title) && !empty($this->properties["acdh:hasWKT"][0]->title)) {
            $data = array_filter(explode(" ", $this->checkMultiPolygonMapString()));
            $coordinate1 = $data[1];
            $coordinate2 = $data[0];
            if (strpos($data[1], ",") !== false) {
                $coordinate1 = substr($data[1], 0, strpos($data[1], ","));
            }
            if (strpos($data[0], ",") !== false) {
                $coordinate2 = substr($data[0], 0, strpos($data[0], ","));
            }
            $str = "[" . $coordinate1 . ", " . $coordinate2 . "]";
        }
        return $str;
    }

    /**
     * Transform Multipolygon string
     * @return string
     */
    private function checkMultiPolygonMapString(): string
    {
        if (strpos(strtolower($this->properties["acdh:hasWKT"][0]->title), 'multipolygon') !== false) {
            return str_replace(')', '', str_replace('(', '', str_replace('MULTIPOLYGON', '', $this->properties["acdh:hasWKT"][0]->title)));
        } elseif (strpos(strtolower($this->properties["acdh:hasWKT"][0]->title), 'polygon') !== false) {
            return str_replace(')', '', str_replace('(', '', str_replace('POLYGON', '', $this->properties["acdh:hasWKT"][0]->title)));
        }
        return "";
    }

    /**
     * Get the WKT map type
     * @return string
     */
    public function getMapType(): string
    {
        if (isset($this->properties["acdh:hasWKT"][0]->title) && !empty($this->properties["acdh:hasWKT"][0]->title)) {
            if (strpos(strtolower($this->properties["acdh:hasWKT"][0]->title), 'multipolygon') !== false) {
                return 'multipolygon';
            } elseif (strpos(strtolower($this->properties["acdh:hasWKT"][0]->title), 'polygon') !== false) {
                return 'polygon';
            }
        }
        return "";
    }

    /**
     * Add Multipolygon string for the polygon dataset, othwerwise the js plugin cant handle it
     * @return string
     */
    public function getPolygonData(): string
    {
        if (isset($this->properties["acdh:hasWKT"][0]->title) && !empty($this->properties["acdh:hasWKT"][0]->title)) {
            if (strpos(strtolower($this->properties["acdh:hasWKT"][0]->title), 'polygon') !== false) {
                $data = str_replace('Polygon', 'MultiPolygon', $this->properties["acdh:hasWKT"][0]->title);
                $data = str_replace('POLYGON', 'MultiPolygon', $this->properties["acdh:hasWKT"][0]->title);
                return $data;
            }
        }
        return "";
    }
    
    /**
     * Check the resource has an audio, to display the audio player
     * @return bool
     */
    public function isAudio(): bool
    {
        $cat = false;
        if (!$this->isPublic()) {
            return false;
        }
        //check the sound categories
        if (isset($this->properties["acdh:hasCategory"])) {
            foreach ($this->properties["acdh:hasCategory"] as $category) {
                if (in_array($category->value, (array)$this->audioCategories)) {
                    $cat = true;
                }
            }
        }
        //check the binarysize
        if (isset($this->properties["acdh:hasBinarySize"][0]->value) &&
                (int)$this->properties["acdh:hasBinarySize"][0]->value > 0 &&
                $cat) {
            return true;
        }

        return false;
    }
    
    /**
     * Check if the resource is a pdf file
     * @return bool
     */
    public function isPDF(): bool
    {
        $isPDF = false;
        if (!$this->isPublic()) {
            return false;
        }
        
        if (isset($this->properties["acdh:hasFormat"])) {
            foreach ($this->properties["acdh:hasFormat"] as $format) {
                if ($format->value == 'application/pdf') {
                    $isPDF = true;
                }
            }
        }
        
        if (isset($this->properties["acdh:hasBinarySize"])) {
            foreach ($this->properties["acdh:hasBinarySize"] as $binary) {
                if ((int)$binary->value > 1 && $isPDF) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * Check the resource is public or not
     * @return bool
     */
    public function isPublic(): bool
    {
        $result = false;
        $access = $this->getAccessRestriction();
        if (
                count((array)$access) > 0 &&
                isset($access['vocabsid']) &&
                $access['vocabsid'] = $this->publicAccessValue) {
            $result = true;
        }
        
        return $result;
    }
    
    /**
     * Create the VCR data json string
     * REDMINE ID: #19076
     * @return string
     */
    public function getVCRData(): string
    {
        $res = new \stdClass();
        
        if (!empty($this->getDataString('acdh:hasDescription'))) {
            $res->description = $this->getDataString('acdh:hasDescription');
        } else {
            if ($this->getAcdhType() == "Resource") {
                $res->description = $this->getDataString('acdh:hasCategory').", ".$this->getDataString('acdh:hasBinarySize');
            } elseif ($this->getAcdhType() == "Collection" || $this->getAcdhType() == "TopCollection") {
                $res->description = $this->getAcdhType().", ".$this->getDataString('acdh:hasNumberOfItems'). ' items';
            } else {
                $res->description = "";
            }
        }
        
        if (!empty($this->getPid())) {
            $res->uri = $this->getPid();
        } else {
            $res->uri = $this->getAcdhID();
        }
        
        $res->label = $this->getTitle();
        
        return \GuzzleHttp\json_encode($res);
    }
    
    /**
     * Get the defined property String values
     * @param string $property
     * @return string
     */
    public function getDataString(string $property): string
    {
        if (isset($this->properties[$property][0]->title) && !empty($this->properties[$property][0]->title)) {
            return $this->properties[$property][0]->title;
        } elseif (isset($this->properties[$property][0]->value) && !empty($this->properties[$property][0]->value)) {
            return $this->properties[$property][0]->value;
        }
        return "";
    }
}
