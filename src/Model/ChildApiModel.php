<?php

namespace Drupal\acdh_repo_gui\Model;

use Drupal\acdh_repo_gui\Model\ArcheModel;

/**
 * Description of ApiModel
 *
 * @author nczirjak
 */
class ChildApiModel extends ArcheModel
{
    protected $repodb;
    private $data = array();
    private $childProperties = array();
    private $rootAcdhType;
    private $sqlTypes;
    protected $siteLang = 'en';

    public function __construct()
    {
        parent::__construct();
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";
    }

    private function getOrganisationTypes(): array
    {
        return array(
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasContributor', $this->repo->getSchema()->__get('namespaces')->ontology . 'hasFunder',
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasOwner', $this->repo->getSchema()->__get('namespaces')->ontology . 'hasLicensor',
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasRightsHolder'
        );
    }

    private function getPublicationTypes(): array
    {
        return array(
            $this->repo->getSchema()->parent
        );
    }

    private function getPersonTypes(): array
    {
        return array(
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasContributor', $this->repo->getSchema()->__get('namespaces')->ontology . 'hasCreator',
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasAuthor', $this->repo->getSchema()->__get('namespaces')->ontology . 'hasEditor',
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasPrincipalInvestigator'
        );
    }

    private function getProjectTypes(): array
    {
        return array(
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasRelatedProject'
        );
    }

    private function getConceptTypes()
    {
        return array(
            'http://www.w3.org/2004/02/skos/core#narrower'
        );
    }

    private function getInstituteTypes(): array
    {
        return array(
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasMember'
        );
    }

    private function getPlaceTypes(): array
    {
        return array(
            $this->repo->getSchema()->__get('namespaces')->ontology . 'hasSpatialCoverage'
        );
    }

    private function getChildTypes(): array
    {
        return array(
            $this->repo->getSchema()->parent
        );
    }

    public function getAcdhtype(): string
    {
        if (!empty($this->rootAcdhType)) {
            return $this->rootAcdhType;
        }
        return '';
    }

    /**
     * Get the actual page view data
     *
     * @param string $identifier
     * @param int $limit
     * @param int $page
     * @param string $orderby
     * @return array
     */
    public function getViewData(string $identifier = "", int $limit = 10, int $page = 0, string $orderby = "titleasc"): array
    {
        $order = $this->ordering($orderby);
        if (empty($this->sqlTypes)) {
            $this->sqlTypes = "ARRAY[]::text[]";
        }

        //get the requested sorting
        try {
            $this->setSqlTimeout('30000');
            // distinct is removing the ordering
            $query = $this->repodb->query(
                "select id, title, avdate, description, accesres, titleimage, acdhtype, version from gui.child_views_func(:id, :limit, :page, :order, :orderprop, :lang, $this->sqlTypes);",
                array(
                        ':id' => $identifier,
                        ':limit' => $limit,
                        ':page' => $page,
                        ':order' => $order->order,
                        ':orderprop' => $order->property,
                        ':lang' => $this->siteLang
                    ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $this->data = $query->fetchAll();
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->data = array();
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            $this->data = array();
        }

        $this->changeBackDBConnection();
        return $this->data;
    }

    /**
     * Get the number of the child resources for the pagination
     *
     * @param string $identifier
     */
    public function getCount(string $identifier): int
    {
        if (empty($this->sqlTypes)) {
            $this->sqlTypes = "ARRAY['" . $this->repo->getSchema()->parent . "']";
        }

        try {
            $this->setSqlTimeout('10000');
            $query = $this->repodb->query(
                "select * from gui.child_sum_views_func(:id, $this->sqlTypes);",
                array(
                        ':id' => $identifier
                    ),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );
            $result = $query->fetch();

            $this->changeBackDBConnection();
            if (isset($result->countid)) {
                return (int) $result->countid;
            }
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
            return 0;
        }
        $this->changeBackDBConnection();
        return 0;
    }

    /**
     * Get the root resource acdh type
     *
     * @param string $repoid
     * @return string
     */
    private function getProperties(string $repoid): string
    {
        $rdf = $this->repo->getSchema()->__get('namespaces')->rdfs . 'type';

        try {
            $this->setSqlTimeout('30000');
            $query = $this->repodb->query(
                "select value from metadata_view where id = :id and property = '" . $rdf . "' and value like '%/vocabs.acdh.oeaw.ac.at/schema#%' limit 1",
                array(':id' => $repoid),
                ['allow_delimiter_in_query' => true, 'allow_square_brackets' => true]
            );

            $result = $query->fetch();
            if (isset($result->value)) {
                return $result->value;
            }
        } catch (\Exception $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        } catch (\Drupal\Core\Database\DatabaseExceptionWrapper $ex) {
            \Drupal::logger('acdh_repo_gui')->notice($ex->getMessage());
        }

        $this->changeBackDBConnection();
        return '';
    }

    /**
     * Get the properties for the sql search by the root resource acdh type
     * @param string $class
     * @return array
     */
    public function getPropertiesByClass(string $repoid)
    {
        $class = $this->getProperties($repoid);
        $property = '';
        $this->rootAcdhType = $class;

        if (!empty($class)) {
            $class = strtolower(str_replace($this->repo->getSchema()->__get('namespaces')->ontology, '', $class));
        }
        $this->checkChildProperties($class);

        //create the sql string array for the query
        $this->formatTypeFilter();
    }

    /**
     * Check the root for the special properties
     * @param string $class
     */
    private function checkChildProperties(string $class)
    {
        switch (strtolower($class)) {
            case 'organisation':
                $this->childProperties = $this->getOrganisationTypes();
                break;
            case 'publication':
                $this->childProperties = $this->getPublicationTypes();
                break;
            case 'person':
                $this->childProperties = $this->getPersonTypes();
                break;
            case 'project':
                $this->childProperties = $this->getProjectTypes();
                break;
            case 'concept':
                $this->childProperties = $this->getConceptTypes();
                break;
            case 'institute':
                $this->childProperties = $this->getInstituteTypes();
                break;
            case 'place':
                $this->childProperties = $this->getPlaceTypes();
                break;
            default:
                $this->childProperties = self::getChildTypes();
                break;
        }
    }

    /**
     * Format the acdh type for the sql query as an array
     * @return string
     */
    private function formatTypeFilter()
    {
        $this->sqlTypes = "";
        if (isset($this->childProperties)) {
            $count = count($this->childProperties);
            if ($count > 0) {
                $this->sqlTypes .= 'ARRAY [ ';
                $i = 0;
                foreach ($this->childProperties as $t) {
                    $this->sqlTypes .= "'$t'";
                    if ($count - 1 != $i) {
                        $this->sqlTypes .= ', ';
                    } else {
                        $this->sqlTypes .= ' ]';
                    }
                    $i++;
                }
            } else {
                $this->sqlTypes = "";
            }
        }
    }
}
