<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of ToolTipObject
 *
 * @author nczirjak
 */
class ToolTipObject
{
    private $data;
    private $td = array();
    private $helper;
    
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->helper = new \Drupal\acdh_repo_gui\Helper\ArcheHelper();
        $this->formatTooltip();
    }
    
    /**
     * Format the sql result for the gui
     * @return array
     */
    private function formatTooltip()
    {
        foreach ($this->data as $t) {
            $sc = (!empty(\Drupal\acdh_repo_gui\Helper\ArcheHelper::createShortcut($t->type)))? \Drupal\acdh_repo_gui\Helper\ArcheHelper::createShortcut($t->type) : $t->type;
            $this->td[$sc] = $t;
        }
    }
    
    public function getData(string $property): object
    {
        return (isset($this->td[$property])) ? $this->td[$property] : new \stdClass();
    }
}
