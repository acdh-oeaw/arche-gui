<?php

namespace Drupal\acdh_repo_gui\Controller;

use Symfony\Component\HttpFoundation\Response;
use acdhOeaw\acdhRepoLib\Repo;
use Drupal\acdh_repo_gui\Controller\RootViewController as RVC;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;

/**
 * Description of AcdhRepoController
 *
 * @author nczirjak
 */
class AcdhRepoGuiController extends \Drupal\Core\Controller\ControllerBase {

    private $config;
    private $repo;
    private $rootViewController;
    private $siteLang;

    public function __construct() {
        $this->config = drupal_get_path('module', 'acdh_repo_gui') . '/config/config.yaml';
        $this->repo = Repo::factory($this->config);
        (isset($_SESSION['language'])) ? $this->siteLang = strtolower($_SESSION['language']) : $this->siteLang = "en";

        $this->rootViewController = new RVC($this->repo);
        $this->generalFunctions = new GeneralFunctions();
    }

    /**
     * Create root view
     *
     * @param string $limit
     * @param string $page
     * @param string $order
     * @return array
     */
    public function repo_root(string $metavalue = "root", string $order = "datedesc", string $limit = "10", string $page = "1"): array {
        $limit = (int) $limit;
        $page = (int) $page;
        // on the gui we are displaying 1 as the first page.
        //$page = $page-1;
        $count = 0;
        $count = $this->rootViewController->countRoots();

        $roots = array();
        $paging = array();
        if ((int) $count > 0) {
            $roots = $this->rootViewController->generateRootView((int) $limit, (int) $page, $order);
        }

        if (!isset($roots['data']) || count($roots['data']) <= 0) {
            \Drupal::messenger()->addWarning($this->t('You do not have Root resources'));
            return array();
        }

        if (count($roots['pagination']) > 0) {
            $paging = $roots['pagination'][0];
        }

        return [
            '#theme' => 'acdh-repo-gui-main',
            '#data' => $roots['data'],
            '#paging' => $paging,
            '#attached' => [
                'library' => [
                    'acdh_repo_gui/repo-root-view',
                ]
            ],
            '#cache' => ['max-age' => 0]
        ];
    }

    /**
     * Change language session variable API
     * Because of the special path handling, the basic language selector is not working
     *
     * @param string $lng
     * @return Response
     */
    public function oeaw_change_lng(string $lng = 'en'): Response {
        $_SESSION['language'] = strtolower($lng);
        $response = new Response();
        $response->setContent(json_encode("language changed to: " . $lng));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     *
     * Displaying the federated login with shibboleth
     *
     * @return array
     */
    public function shibboleth_login(): array {
        $result = array();
        $userid = \Drupal::currentUser()->id();
        if ((isset($_SERVER['HTTP_EPPN']) && $_SERVER['HTTP_EPPN'] != "(null)") && (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] != "(null)")
        ) {
            \Drupal::messenger()->addStatus($this->t('You are logged in as ' . $_SERVER['HTTP_EPPN']));

            //if we already logged in with shibboleth then login the user with the shibboleth account
            $this->generalFunctions->handleShibbolethUser();
            return $result;
        } else {
            $result = array(
                        '#cache' => ['max-age' => 0,],
                        '#theme' => 'acdh-repo-gui-shibboleth-login'
            );
        }

        return $result;
    }

}
