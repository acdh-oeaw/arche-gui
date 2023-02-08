<?php

namespace Drupal\acdh_repo_gui\EventSubscriber;

use Drupal\User\Entity\User;
use Drupal\Core\DrupalKernel;
// This is the interface we are going to implement.
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
// This class contains the event we want to subscribe to.
use Symfony\Component\HttpKernel\KernelEvents;
// Our event listener method will receive one of these.
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
// We'll use this to perform a redirect if necessary.
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Routing\TrustedRedirectResponse;

//use Drupal\oeaw\OeawFunctions;
//use acdhOeaw\util\RepoConfig as RC;

class AcdhRepoGuiEventSubscriber implements EventSubscriberInterface
{
    public function setupMainClasses()
    {
    }
    /**
     * Check the shibboleth user logins
     *
     * @global type $user
     * @param GetResponseEvent $event
     * @return TrustedRedirectResponse
     */
    
    public function checkForShibboleth(GetResponseEvent $event)
    {
        global $user;
         
        $this->logoutShibbolethUserManually($event);
        
        $this->loginShibbolethUser($event);
        
        $this->logOutShibbolethUser();
    }
    
    /**
     * login the shibboleth user
     *
     * @param GetResponseEvent $event
     * @return TrustedRedirectResponse
     */
    private function loginShibbolethUser(GetResponseEvent &$event)
    {
        if ($event->getRequest()->getPathInfo() == '/federated_login') {
           
            //the actual user id, if the user is logged in
            $userid = \Drupal::currentUser()->id();
            //if it is a shibboleth login and there is no user logged in
            if (isset($_SERVER['HTTP_EPPN'])
                    && $_SERVER['HTTP_EPPN'] != "(null)"
                    && $userid == 0
                    && \Drupal::currentUser()->isAnonymous()) {
                $gF = new \Drupal\acdh_repo_gui\Helper\GeneralFunctions();
                $gF->handleShibbolethUser();

                $host = \Drupal::request()->getSchemeAndHttpHost();
                return new TrustedRedirectResponse($host."/browser/federated_login/");
            }
        }
    }

    /**
     * If the user clicks the logout button
     * @param GetResponseEvent $event
     * @return void
     */
    private function logoutShibbolethUserManually(GetResponseEvent &$event): void
    {
        if (($event->getRequest()->getPathInfo() == '/user/logout') && $this->checkUserShibbolethRole()) {
            unset($_SERVER['HTTP_AUTHORIZATION']);
            unset($_SERVER['HTTP_EPPN']);
            $_SERVER['HTTP_AUTHORIZATION'] = "";
            $_SERVER['HTTP_EPPN'] = "";
            foreach (headers_list() as $header) {
                header_remove($header);
            }
            $host = \Drupal::request()->getSchemeAndHttpHost();
            \Drupal::service('session_manager')->delete(\Drupal::currentUser()->id());
            $event->setResponse(new TrustedRedirectResponse($host."/Shibboleth.sso/Logout?return=".$host."/browser/"));
        }
    }
    
    /**
     * Check the user shibboleth role
     * @return bool
     */
    private function checkUserShibbolethRole(): bool
    {
        if (\Drupal::currentUser()->isAuthenticated() && count(\Drupal::currentUser()->getRoles()) > 0) {
            foreach (\Drupal::currentUser()->getRoles() as $v) {
                if (strpos(strtolower($v), 'shibboleth') !== false) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * logout the shibboleth user if we dont have a HTTP_EPPN value
     * @return void
     */
    private function logOutShibbolethUser(): void
    {
        //check the logged in user role, if the eppn is null then logout
        if (\Drupal::currentUser()->isAuthenticated()
            && (!isset($_SERVER['HTTP_EPPN'])
            || (isset($_SERVER['HTTP_EPPN']) &&  $_SERVER['HTTP_EPPN']!= "(null)"))) {
            if ($this->checkUserShibbolethRole()) {
                \Drupal::service('session_manager')->delete(\Drupal::currentUser()->id());
            }
        }
    }

    /**
     * This is the event handler main method
     *
     * @return string
     */
    public static function getSubscribedEvents()
    {
        $events = [];
        $events[KernelEvents::REQUEST][] = array('checkForShibboleth', 300);
        return $events;
    }
}
