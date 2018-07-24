<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCampaignWatchBundle\Integration;

use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Class CampaignWatchIntegration.
 */
class CampaignWatchIntegration extends AbstractIntegration
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'CampaignWatch';
    }

    /**
     * @return string
     */
    public function getDisplayName()
    {
        return 'Campaign Watch';
    }

    /**
     * @return string
     */
    public function getAuthenticationType()
    {
        return 'none';
    }
}
