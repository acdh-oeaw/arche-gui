<?php

namespace Drupal\acdh_repo_gui\Model;

use acdhOeaw\arche\lib\Repo;

/**
 * Description of ArcheModel
 *
 * @author nczirjak
 */
abstract class ArcheModel
{
    protected $repodb;
    protected $config;
    protected $repo;
    protected $limit;
    protected $order;
    protected $offset;

    public function __construct()
    {
        $this->config = \Drupal::service('extension.list.module')->getPath('acdh_repo_gui') . '/config/config.yaml';
        try {
            $this->repo = \acdhOeaw\arche\lib\Repo::factory($this->config);
        } catch (\Exception $ex) {
            \Drupal::messenger()->addWarning($this->t('Error during the BaseController initialization!').' '.$ex->getMessage());
            return array();
        }
        //set up the DB connections
        $this->setActiveConnection();
    }

    /**
     * Allow the DB connection
     */
    protected function setActiveConnection()
    {
        \Drupal\Core\Database\Database::setActiveConnection('repo');
        $this->repodb = \Drupal\Core\Database\Database::getConnection('repo');
    }

    protected function changeBackDBConnection()
    {
        \Drupal\Core\Database\Database::setActiveConnection();
    }

    /**
     * Set the sql execution max time
     * @param string $timeout
     */
    public function setSqlTimeout(string $timeout = '7000')
    {
        $this->setActiveConnection();

        try {
            $this->repodb->query(
                "SET statement_timeout TO :timeout;",
                array(':timeout' => $timeout)
            )->fetch();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }
    }

    /**
     * Reorder the Easyrdf result because the dataset is already filtered by the API
     * But the actual order is not possible from the easyrdf, so we have to sort is
     * manually..
     * @param array $array
     * @param string $key
     * @param string $direction
     * @return array
     */
    protected function sortAssociativeArrayByKey(array $array, string $key, string $direction): array
    {
        switch ($direction) {
            case "ASC":
                usort($array, function ($first, $second) use ($key) {
                    if (isset($first[$key]) && isset($second[$key])) {
                        return $first[$key] <=> $second[$key];
                    }
                });
                break;
            case "DESC":
                usort($array, function ($first, $second) use ($key) {
                    if (isset($first[$key]) && isset($second[$key])) {
                        return $second[$key] <=> $first[$key];
                    }
                });
                break;
            default:
                break;
        }

        return $array;
    }
    
    
    
    /**
     * Reorder the result because of the easyrdf
     * @param array $data
     * @param string $order
     * @return array
     */
    protected function reOrderResult(array $data, string $order): array
    {
        if (count($data) == 0) {
            return array();
        }

        $orderBy = "ASC";

        if (strpos($order, "^") !== false) {
            $orderBy = "DESC";
            $order = str_replace("^", "", $order);
        }

        return $this->sortAssociativeArrayByKey($data, $order, $orderBy);
    }
    
    /**
     * Create the order values for the sql
     *
     * @param string $orderby
     * @return object
     */
    protected function ordering(string $orderby = "titleasc"): object
    {
        $result = new \stdClass();
        $result->property = $this->repo->getSchema()->label;
        $result->order = 'asc';

        if ($orderby == "titleasc") {
            $result->property = $this->repo->getSchema()->label;
            $result->order = 'asc';
        } elseif ($orderby == "titledesc") {
            $result->property = $this->repo->getSchema()->label;
            $result->order = 'desc';
        } elseif ($orderby == "dateasc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'asc';
        } elseif ($orderby == "datedesc") {
            $result->property = 'http://fedora.info/definitions/v4/repository#lastModified';
            $result->order = 'desc';
        } elseif ($orderby == "typeasc") {
            $result->property = $this->repo->getSchema()->__get('namespaces')->rdfs . 'type';
            $result->order = 'asc';
        } elseif ($orderby == "typedesc") {
            $result->property = $this->repo->getSchema()->__get('namespaces')->rdfs . 'type';
            $result->order = 'desc';
        }
        return $result;
    }
    
    
    protected function orderingByFields(array $valuesAndFields, string $orderby = "titleasc"): object
    {
        $result = new \stdClass();
        $result->property = 'title';
        $result->order = 'asc';
        
        if ($orderby == "titleasc") {
            $result->property = $valuesAndFields['titleasc'];
            $result->order = 'asc';
        } elseif ($orderby == "titledesc") {
            $result->property = $valuesAndFields['titledesc'];
            $result->order = 'desc';
        } elseif ($orderby == "dateasc") {
            $result->property = $valuesAndFields['dateasc'];
            $result->order = 'asc';
        } elseif ($orderby == "datedesc") {
            $result->property = $valuesAndFields['datedesc'];
            $result->order = 'desc';
        } elseif ($orderby == "typeasc") {
            $result->property = $valuesAndFields['typeasc'];
            $result->order = 'asc';
        } elseif ($orderby == "typedesc") {
            $result->property = $valuesAndFields['typedesc'];
            $result->order = 'desc';
        }
        return $result;
    }

    /**
     * get the views data
     *
     * @return array
     */
    abstract public function getViewData(): array;
}
