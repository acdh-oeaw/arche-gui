<?php

class exampleData
{
    public static function exampleResourceData()
    {
        $resourceData = array();
        $data = new \stdClass();
        $data->id = 345;
        $data->value = 'my example title';
        $data->title = 'my example title';
        $data->property = "https://vocabs.acdh.oeaw.ac.at/schema#hasTitle";
        $resourceData["acdh:hasTitle"]['en'] = array($data);
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $data->type = 'ID';
        $data->value = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = null;
        $data->uri = 'https://arche-dev.acdh-dev.oeaw.ac.at/api/244468';
        $resourceData["acdh:hasIdentifier"]['en'][] = $data;
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $data->type = 'ID';
        $data->value = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        $data->relvalue = null;
        $data->acdhid = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = null;
        $data->uri = 'https://id.acdh.oeaw.ac.at/wollmilchsau/example';
        $resourceData["acdh:hasIdentifier"]['en'][] = $data;
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $data->type = 'ID';
        $data->value = 'https://external.identifier.com';
        $data->relvalue = null;
        $data->acdhid = 'https://external.identifier.com';
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = null;
        $data->uri = 'https://external.identifier.com';
        $resourceData["acdh:hasIdentifier"]['en'][] = $data;
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAccesssRestriction';
        $data->type = 'REL';
        $data->value = '4685';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = 4685;
        $data->accessrestriction = '';
        $data->language = null;
        $data->uri = 'https://vocabs.acdh.oeaw.ac.at/accesrestriction/public';
        $resourceData["acdh:hasAccessRestriction"]['en'] = array($data);
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasIdentifier';
        $data->type = 'ID';
        $data->value = 'https://example.pid.com';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = null;
        $data->uri = 'https://example.pid.com';
        $resourceData["acdh:hasPid"]['en'] = array($data);
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='https://vocabs.acdh.oeaw.ac.at/schema#hasAvailableDate';
        $data->type = 'http://www.w3.org/2001/XMLSchema#date';
        $data->value = '2020-07-28 09:39:29';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = null;
        $data->title = '2017-10-03';
        $data->shortcut = 'acdh:hasAvailableDate';
        $resourceData["acdh:hasAvailableDate"]['en'] = array($data);
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='rdf:type';
        $data->type = 'string';
        $data->value = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = 'en';
        $data->title = 'https://vocabs.acdh.oeaw.ac.at/schema#Collection';
        $data->shortcut = 'rdf:type';
        $resourceData["rdf:type"]['en'][] = $data;
        
        $data = new \stdClass();
        $data->id = 345;
        $data->property ='rdf:type';
        $data->type = 'string';
        $data->value = 'http://www.w3.org/2004/02/skos/core#Skostype';
        $data->relvalue = null;
        $data->acdhid = null;
        $data->vocabsid = null;
        $data->accessrestriction = '';
        $data->language = 'en';
        $data->title = 'http://www.w3.org/2004/02/skos/core#Skostype';
        $data->shortcut = 'rdf:type';
        $resourceData["rdf:type"]['en'][] = $data;
        
        return $resourceData;
    }
}


?>

