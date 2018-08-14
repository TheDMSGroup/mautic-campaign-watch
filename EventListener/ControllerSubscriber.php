<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCampaignWatchBundle\EventListener;

use Mautic\CampaignBundle\Controller\CampaignController;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use MauticPlugin\MauticCampaignWatchBundle\Controller\CampaignControllerOverride;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Class CustomContentSubscriber.
 */
class ControllerSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER => ['getController', 0],
        ];
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function getController(FilterControllerEvent $event)
    {
        /** @var array $controller */
        $controller = $event->getController();
        if (
            is_array($controller)
            && isset($controller[0])
            && isset($controller[1])
            && 'contactsAction' === $controller[1]
            && $controller[0] instanceof CampaignController
            && ($request = $event->getRequest())
            && (
                !$request->isXmlHttpRequest()
                || $event->getRequest()->get('ignoreAjax')
            )
        ) {
            $controller = new CampaignControllerOverride();
            $controller->setRequest($request);
            $controller->setContainer($this->dispatcher->getContainer());
            $event->setController([$controller, 'contactsAction']);
        }
    }
}
