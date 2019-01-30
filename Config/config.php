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
            'mautic_campaignwatch_contacts_export' => [
                'path'       => '/campaignwatch/export/{campaignId}/{dateFrom}/{dateTo}',
                'controller' => 'MauticCampaignWatchBundle:Export:campaignContactsExport',
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
            'mautic.campaignwatch.reportbundle.subscriber' => [
                'class'     => 'MauticPlugin\MauticCampaignWatchBundle\EventListener\ReportSubscriber',
                'arguments' => [
                    'mautic.lead.model.lead',
                    'mautic.campaign.model.campaign',
                    'mautic.lead.reportbundle.fields_builder',
                ],
            ],
        ],
    ],
];
