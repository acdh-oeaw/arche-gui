<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of ClarinVCRObject
 *
 * @author nczirjak
 */
class ClarinVCRObject
{
    private $urls;
    private $url;
    private $clarinUrl;
    private $client;
    private $collectionName = "ArcheCollection";
    private $header = array();
    private $form_params;

    public function __construct(string $urls)
    {
        $this->urls = $urls;
        $this->processUrls();
        $this->createHeader();
        $this->createFormParams();
    }

    private function processUrls()
    {
        $urls = explode(":", $this->urls);
        $url = "";

        foreach ($urls as $ra) {
            if (strpos($ra, '&') !== false) {
                $pos = strpos($ra, '&');
                $ra = substr($ra, 0, $pos);
                $url .= $ra . "/";
            } else {
                $url .= $ra . "/";
            }
        }
        $url = str_replace('http//', 'http://', $url);
        $url = str_replace('https//', 'https://', $url);
        $this->url = $url;
    }

    private function createHeader(): void
    {
        $this->header = [
            'Content-Type' => 'application/x-www-form-urlencoded',
            'Referer' => 'https://arche.acdh.oeaw.ac.at'
        ];
    }

    private function createFormParams(): void
    {
        $this->form_params = [
            "name" => $this->collectionName,
            "description" => $this->collectionName,
            "resourceUri" => $this->url,
            "metadataUri" => $this->url
        ];
    }

    public function makeTheApiCall(bool $isTest = false): string
    {
        $this->setTheUrl($isTest);
        $this->setupTheClient();
        
        try {
            $request = $this->client->post($this->clarinUrl, [
                'headers' => $this->header,
                'form_params' => $this->form_params,
                'curl' => [
                    CURLOPT_RETURNTRANSFER => true
                ],
                'allow_redirects' => [
                    'track_redirects' => true
                ]
            ]);
            return $this->checkHeaderRedirect($request);
        } catch (\GuzzleHttp\Exception\ClientException $ex) {
            error_log($ex->getMessage());
            return "";
        } catch (\Exception $ex) {
            error_log($ex->getMessage());
            return "";
        }
        return "";
    }

    private function setTheUrl(bool $isTest = false): void
    {
        ($isTest) ? $this->clarinUrl = "https://collections.clarin-dev.eu/submit/extensional" : $this->clarinUrl = "https://collections.clarin.eu/submit/extensional";
    }

    private function setupTheClient(): void
    {
        $this->client = new \GuzzleHttp\Client(
            ['verify' => false]
        );
    }

    private function checkHeaderRedirect(\GuzzleHttp\Psr7\Response &$request): string
    {
        if ($request->getStatusCode() == 200) {
            if ($request->getHeaderLine('X-Guzzle-Redirect-History') !== null && !empty($request->getHeaderLine('X-Guzzle-Redirect-History'))) {
                return (string)$request->getHeaderLine('X-Guzzle-Redirect-History');
            }
        }
        return "";
    }
}