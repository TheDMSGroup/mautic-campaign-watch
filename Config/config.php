<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

return [
    'name'        => 'Campaign Watch',
    'description' => 'Visual enhancements for Campaigns in Mautic',
    'version'     => '1.0',
    'author'      => 'Heath Dutton',
    'routes'      => [
        'main' => [
            'mautic_campaignwatch_contacts' => [
                'path'       => '/campaignwatch/view/{objectId}/contact/{page}',
                'controller' => 'MauticCampaignWatchBundle:Campaign:contacts',
            ],
        ],
    ],
    'services'    => [
        'events' => [
            'mautic.campaignwatch.subscriber.controller'    => [
                'class' => 'MauticPlugin\MauticCampaignWatchBundle\EventListener\ControllerSubscriber',
            ],
            'mautic.campaignwatch.subscriber.customcontent' => [
                'class' => 'MauticPlugin\MauticCampaignWatchBundle\EventListener\CustomContentSubscriber',
            ],
        ],
    ],
];
