<?php

namespace Drupal\acdh_repo_gui\Controller;

use Symfony\Component\HttpFoundation\Response;
use acdhOeaw\arche\lib\Repo;
use Drupal\acdh_repo_gui\Controller\RootViewController as RVC;
use Drupal\acdh_repo_gui\Helper\GeneralFunctions;

/**
 * Description of AcdhRepoController
 *
 * @author nczirjak
 */
class AcdhRepoGuiController extends \Drupal\acdh_repo_gui\Controller\ArcheBaseController
{
    private $rootViewController;

    public function __construct()
    {
        parent::__construct();
        $this->rootViewController = new RVC($this->repo);
        $this->generalFunctions = new GeneralFunctions();
    }

    /**
     * Change language session variable API
     * Because of the special path handling, the basic language selector is not working
     *
     * @param string $lng
     * @return Response
     */
    public function oeaw_change_lng(string $lng = 'en'): Response
    {
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
    public function shibboleth_login(): array
    {
        $result = array();
        $userid = \Drupal::currentUser()->id();
        if ((isset($_SERVER['HTTP_EPPN']) && $_SERVER['HTTP_EPPN'] != "(null)") && (isset($_SERVER['HTTP_AUTHORIZATION']) && $_SERVER['HTTP_AUTHORIZATION'] != "(null)")
        ) {
            \Drupal::messenger()->addStatus($this->t('You are logged in as ' . $_SERVER['HTTP_EPPN']));

            //if we already logged in with shibboleth then login the user with the shibboleth account
            $this->generalFunctions->handleShibbolethUser($_SERVER['HTTP_EPPN'], $_SERVER['HTTP_EMAIL']);
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
