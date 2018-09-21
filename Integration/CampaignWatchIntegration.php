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

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
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

    public function appendToForm(&$builder, $data, $formArea)
    {
        if ('features' === $formArea) {
            $builder->add(
                'campaign_detail_stat_chart_toggle',
                YesNoButtonGroupType::class,
                [
                    'label'       => 'mautic.campaignwatch.stat_chart.toggle.label',
                    'label_attr'  => [
                        'class' => 'control-label',
                    ],
                    'data'        => isset($data['campaign_detail_stat_chart_toggle']) ? (bool) $data['campaign_detail_stat_chart_toggle'] : false,
                    'attr'        => [
                        'class'   => 'form-control',
                        'tooltip' => $this->translator->trans('mautic.campaignwatch.stat_chart.toggle.tooltip'),
                    ],
                ]
            );
        }
    }
}
