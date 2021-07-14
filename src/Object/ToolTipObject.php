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
    public function __construct(array $data)
    {
        $this->data = $data;
        $this->formatTooltip();
    }
    
    /**
     * Format the sql result for the gui
     * @return array
     */
    private function formatTooltip()
    {
        foreach ($this->data as $t) {
            $this->td[$t->type] = $t;
        }
    }
    
    public function getData(string $property): object
    {
        return (isset($this->td[$property])) ? $this->td[$property] : new \stdClass();
    }
}
