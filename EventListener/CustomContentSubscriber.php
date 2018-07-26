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

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\CustomContentEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;

/**
 * Class CustomContentSubscriber.
 */
class CustomContentSubscriber extends CommonSubscriber
{
    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CoreEvents::VIEW_INJECT_CUSTOM_CONTENT => ['getContentInjection', 0],
        ];
    }

    /**
     * @param CustomContentEvent $event
     */
    public function getContentInjection(CustomContentEvent $event)
    {
        switch ($event->getViewName()) {
            case 'MauticCampaignBundle:Campaign:details.html.php':
                switch ($event->getContext()) {
                    case 'tabs':
                        // Add tab.
                    case 'tab.content':
                        // Add tab content.
                }
                break;
        }
    }
}
