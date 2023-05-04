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

class OpenAirTrackingEventSubscriber implements EventSubscriberInterface
{
    public function setupMainClasses()
    {
    }
    /**
     * send tracking info to openair tracking server
     *
     * @global type $user
     * @param GetResponseEvent $event
     * @return TrustedRedirectResponse
     */
    

    public function checkForOpenAirTracking(GetResponseEvent $event)
    {
        if (strpos($event->getRequest()->getPathInfo(), '/detail/') !== false) {
            error_log('OPENAIR TRACKING....');
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
        $events[KernelEvents::REQUEST][] = array('checkForOpenAirTracking', 300);
        return $events;
    }
}
