<?php

namespace Drupal\acdh_repo_gui\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Drupal\Core\Url;
use acdhOeaw\acdhRepoLib\Repo;

class AcdhRepoGuiConfigEventSubscriber implements EventSubscriberInterface
{
     public function initRepoGuiCfg(GetResponseEvent $event)
    {
        global $archeCfg;
        $archeCfg = Repo::factory($_SERVER["DOCUMENT_ROOT"].'/modules/custom/acdh_repo_gui/config.yaml');
    }
    
    /**
    * Listen to kernel.request events and call customRedirection.
    * {@inheritdoc}
    * @return array Event names to listen to (key) and methods to call (value)
    */
    public static function getSubscribedEvents()
    {
        $events[KernelEvents::REQUEST][] = array('initRepoGuiCfg');
        return $events;
    }
}
