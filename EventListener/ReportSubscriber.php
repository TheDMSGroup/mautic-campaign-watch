<?php

/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCampaignWatchBundle\EventListener;

use Mautic\CampaignBundle\Model\CampaignModel;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Report\FieldsBuilder;
use Mautic\ReportBundle\Event\ReportBuilderEvent;
use Mautic\ReportBundle\Event\ReportGeneratorEvent;
use Mautic\ReportBundle\ReportEvents;

/**
 * Class ReportSubscriber.
 */
class ReportSubscriber extends CommonSubscriber
{
    const CONTEXT_CAMPAIGN_WATCH_LEADCAMPAIGN_STATS = 'campaignwatch_leadcampaign_stats';

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CampaignModel
     */
    protected $campaignModel;

    /**
     * @var FieldsBuilder
     */
    private $fieldsBuilder;

    /**
     * @param LeadModel     $leadModel
     * @param CampaignModel $campaignModel
     * @param FieldsBuilder $fieldsBuilder
     */
    public function __construct(
        LeadModel $leadModel,
        CampaignModel $campaignModel,
        FieldsBuilder $fieldsBuilder
    ) {
        $this->leadModel     = $leadModel;
        $this->campaignModel = $campaignModel;
        $this->fieldsBuilder = $fieldsBuilder;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            ReportEvents::REPORT_ON_BUILD    => ['onReportBuilder', 0],
            ReportEvents::REPORT_ON_GENERATE => ['onReportGenerate', 0],
        ];
    }

    /**
     * Add available tables and columns to the report builder lookup.
     *
     * @param ReportBuilderEvent $event
     */
    public function onReportBuilder(ReportBuilderEvent $event)
    {
        $campaignPrefix          = 'c.';
        $campaignAliasPrefix     = 'c_';
        $campaignLeadPrefix      = 'cl.';
        $campaignLeadAliasPrefix = 'cl_';

        $utmPrefix      = 'u.';
        $utmAliasPrefix = 'u_';

        $columns = [
            $campaignPrefix.'name' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.name',
                'type'  => 'string',
                'alias' => $campaignAliasPrefix.'name',
            ],

            $campaignPrefix.'id' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.id',
                'type'  => 'string',
                'alias' => $campaignAliasPrefix.'id',
            ],

            $campaignPrefix.'is_published' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.ispublished',
                'type'  => 'int',
                'alias' => $campaignAliasPrefix.'is_published',
            ],

            $campaignLeadPrefix.'rotation' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.rotation',
                'type'  => 'string',
                'alias' => $campaignLeadAliasPrefix.'rotation',
            ],

            $campaignLeadPrefix.'manually_removed' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.manually_removed',
                'type'  => 'string',
                'alias' => $campaignLeadAliasPrefix.'manually_removed',
            ],

            $campaignLeadPrefix.'manually_added' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.manually_added',
                'type'  => 'string',
                'alias' => $campaignLeadAliasPrefix.'manually_added',
            ],

            $campaignLeadPrefix.'date_added' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.date_added',
                'type'  => 'string',
                'alias' => $campaignLeadAliasPrefix.'date_added',
            ],

            $utmPrefix.'utm_campaign' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.utm_campaign',
                'type'  => 'string',
                'alias' => $utmAliasPrefix.'utm_campaign',
            ],

            $utmPrefix.'utm_source' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.utm_source',
                'type'  => 'string',
                'alias' => $utmAliasPrefix.'utm_source',
            ],

            $utmPrefix.'utm_term' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.utm_term',
                'type'  => 'string',
                'alias' => $utmAliasPrefix.'utm_term',
            ],

            $utmPrefix.'utm_medium' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.utm_medium',
                'type'  => 'string',
                'alias' => $utmAliasPrefix.'utm_medium',
            ],

            $utmPrefix.'utm_content' => [
                'label' => 'mautic.campaignwatch.leadcampaign.header.utm_content',
                'type'  => 'string',
                'alias' => $utmAliasPrefix.'utm_content',
            ],
        ];

        $mergedColumns = array_merge(
            $this->fieldsBuilder->getLeadFieldsColumns('l.'),
            $columns
        );

        $data = [
            'display_name' => 'mautic.widget.leadcampaign.stats',
            'columns'      => $mergedColumns,
        ];
        $event->addTable(self::CONTEXT_CAMPAIGN_WATCH_LEADCAMPAIGN_STATS, $data, 'contacts');
    }

    /**
     * @param ReportGeneratorEvent $event
     *
     * @throws \Exception
     */
    public function onReportGenerate(ReportGeneratorEvent $event)
    {
        $qb       = $event->getQueryBuilder();
        $dateFrom = $event->getOptions()['dateFrom'];
        $dateTo   = $event->getOptions()['dateTo'];

        $dateOffset = [
            'DAILY'   => '-1 day',
            'WEEKLY'  => '-7 days',
            'MONTHLY' => '- 30 days',
        ];
        if (empty($event->getReport()->getScheduleUnit())) {
            $dateShift = '- 30 days';
        } else {
            $dateShift = $dateOffset[$event->getReport()->getScheduleUnit()];
        }

        if ($event->checkContext(self::CONTEXT_CAMPAIGN_WATCH_LEADCAMPAIGN_STATS)) {
            $qb->leftJoin('l', MAUTIC_TABLE_PREFIX.'campaign_leads', 'cl', 'cl.lead_id = l.id', 'date_added')
                ->leftJoin('cl', MAUTIC_TABLE_PREFIX.'campaigns', 'c', 'c.id = cl.campaign_id')
                ->leftjoin('l', MAUTIC_TABLE_PREFIX.'lead_utmtags', 'u', 'l.id = u.lead_id');
        } else {
            return;
        }

        if (empty($dateFrom)) {
            $dateFrom = new \DateTime('now midnight', new \DateTimeZone('UTC'));
            $dateFrom->modify($dateShift);
        }

        if (empty($dateTo)) {
            $dateTo = new \DateTime('now midnight -1 sec', new \DateTimeZone('UTC'));
        }

        $dateFromShifted = new \DateTime($dateFrom->format('Y-m-d H:i:s'), new \DateTimeZone($this->factory->getParameter('default_timezone')));
        $dateToShifted = new \DateTime($dateTo->format('Y-m-d H:i:s'), new \DateTimeZone($this->factory->getParameter('default_timezone')));



        $qb->andWhere('cl.date_added BETWEEN FROM_UNIXTIME(:dateFrom) AND FROM_UNIXTIME(:dateTo)')
            ->setParameter('dateFrom', $dateFromShifted->getTimestamp())
            ->setParameter('dateTo', $dateToShifted->getTimestamp());

        $qb->from(MAUTIC_TABLE_PREFIX.'leads', 'l');

        $event->setQueryBuilder($qb);
    }
}
