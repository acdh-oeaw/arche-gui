<?php

namespace Drupal\acdh_repo_gui\TwigExtension;

class ComposerTwigExtension extends \Twig_Extension
{

    /**
     * {@inheritdoc}
     * This function must return the name of the extension. It must be unique.
     */
    public function getName()
    {
        return 'acdh_repo_gui_composer.twig_extension';
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('get_acdh_composer_versions', [$this, 'get_acdh_composer_versions']),
        ];
    }

    public function get_acdh_composer_versions(string $name = "")
    {
        $composerContent = $this->getComposerFile('/home/www-data/gui/composer.lock');
         
        $str = $this->processGuiComposerFile($composerContent);
        if (!empty($str)) {
            $str = "Versions: ".$str;
        }
        return $str;
    }
    
    private function processGuiComposerFile(array $file): string
    {
        $str = "";
        if (count((array)$file) > 0) {
            foreach ($file as $v) {
                if (strpos($v->name, 'acdh-oeaw/') !== false) {
                    $str.= str_replace('acdh-oeaw/', '', $v->name).":".$v->version.". | ";
                }
            }
        }
        return $str;
    }
    
    private function getComposerFile(string $url): array
    {
        $data = new \stdClass();
        $data = json_decode(file_get_contents($url));
        if (count((array)$data) == 0) {
            return new \stdClass();
        }
        
        if (count((array)$data->packages) == 0) {
            return new \stdClass();
        }
        
        return $data->packages;
    }
}
