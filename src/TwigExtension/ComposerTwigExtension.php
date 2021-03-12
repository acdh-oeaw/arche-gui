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

    public function get_acdh_composer_versions($name)
    {
        $str = "";
        $composerLibraries = array();
        
        $composerContent = $this->getComposerFile('/home/www-data/gui/composer.json');
        $coreComposerContent = $this->getComposerFile('/home/www-data/config/composer.json');
                
        $str = $this->processGuiComposerFile($composerContent);
        $str .= $this->processCoreComposerFile($coreComposerContent);
        if (!empty($str)) {
            $str = "Versions: ".$str;
        }
        return $str;
    }
    
    private function processGuiComposerFile(object $file): string
    {
        $str = "";
        if (count((array)$file) > 0) {
            foreach ($file as $k => $v) {
                if ($k == "require") {
                    foreach ($v as $package => $version) {
                        if (strpos($package, "acdh-oeaw/") !== false) {
                            switch ($package) {
                                case 'acdh-oeaw/arche-gui':
                                    $str .= " GUI:".$version."| ";
                                    break;
                                case 'acdh-oeaw/arche-dashboard':
                                    $str .= " Dashboard:".$version."| ";
                                    break;
                                case 'acdh-oeaw/arche-theme':
                                    $str .= " Theme:".$version."| ";
                                    break;
                                default:
                                    break;
                            }
                        }
                    }
                }
            }
        }
        return $str;
    }
    
    private function processCoreComposerFile(object $file): string
    {
        $str = "";
        if (count((array)$file) > 0) {
            foreach ($file as $k => $v) {
                if ($k == "require") {
                    foreach ($v as $package => $version) {
                        if (strpos($package, "acdh-oeaw/") !== false) {
                            $str .= str_replace('acdh-oeaw/arche-', '', $package).":".$version."| ";
                        }
                    }
                }
            }
        }
        return $str;
    }
    
    
    private function getComposerFile(string $url): object
    {
        $data = new \stdClass();
        $data = json_decode(file_get_contents($url));
        if (count((array)$data) == 0) {
            return new \stdClass();
        }
        return $data;
    }
}
