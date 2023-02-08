<?php

namespace Drupal\acdh_repo_gui\Object;

/**
 * Description of BreadCrumbObject
 *
 * @author nczirjak
 */
class BreadCrumbObject
{
    private $data = array();
    private $length = 0;
    private $str = "";
    private $i = 0;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->length = count((array) $data);
    }

    /**
     * Generate the breadcrumb navigation to the gui
     * @return string
     */
    public function getBreadCrumb(): string
    {
        $multiple = $this->checkMultipleBreadCrumb();
       
        if (count($multiple) > 1) {
            $this->createMultiBreadcrumb($this->reOrderBreadCrumbByDate($multiple));
        } else {
            $this->createSingleBreadcrumb();
        }

        return $this->str;
    }

    /**
     * Create the breadcrumb title
     * @param int $k
     * @param object $v
     * @return string
     */
    private function createTitle(int $k, object $v): string
    {
        if ((int)$this->length > 3 && (int)$k > 0 && (int)$k < (int)$this->length - 1) {
            return "...";
        } else {
            return $v->parenttitle;
        }
    }

    /**
     * We can have resources with multiple parents
     * @return array
     */
    private function checkMultipleBreadCrumb(): array
    {
        return array_filter($this->data, function ($item) {
            return ((int)$item->depth === 1);
        });
    }

    /**
     * Single one parent breadcrumb
     */
    private function createSingleBreadcrumb()
    {
        foreach ($this->data as $k => $v) {
            if ($v->parenttitle) {
                $this->str .= "<a id='archeHref' href='/browser/oeaw_detail/" . $v->parentid . "' title='" . $v->parenttitle . "'>" . $this->createTitle($k, $v) . "</a> ";
                if ((int)$this->length - 1 >= (int) $k) {
                    $this->str .= "/";
                }
            }
        }
    }

    /**
     * Create the string from the multiple breadcrumbs
     * @param array $multiple
     */
    private function createMultiBreadcrumb(array $multiple)
    {
        foreach ($multiple as $m) {
            $this->i = 0;
            $this->str .= '<i class="material-icons breadcrumb-icon">label</i>';
            $this->buildTree($this->data, $m->parentid);
            $this->str .= "<br>";
        }
    }

    /**
     * Recursive function to iterare trough the array to get the breadcrumb elements
     * @param array $elements
     * @param type $parentId
     * @return array
     */
    private function buildTree(array $elements, $parentId = 0)
    {
        $branch = array();
        foreach ($elements as $element) {
            if ($element->parentid == $parentId) {
                $this->str .= "<a id='archeHref' href='/browser/oeaw_detail/" . $element->parentid . "' title='" . $element->parenttitle . "'>" . $this->createTitle($this->i, $element) . "</a> / ";
                $this->buildTree($elements, $element->direct_parent);
                $this->i++;
            }
        }
        return $branch;
    }

    /**
     * We have to reorder the actual roots by the avilable date
     */
    private function reOrderBreadCrumbByDate(array $roots): array
    {
        uasort($roots, function ($a, $b) {
            return strcmp($a->avdate, $b->avdate);
        });
        return $roots;
    }
}
