<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticCampaignWatchBundle\Controller;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Controller\CommonController;
use MauticPlugin\MauticSocialBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CampaignControllerOverride.
 */
class ExportController extends CommonController
{
    public function campaignContactsExportAction($campaignId, $dateFrom, $dateTo)
    {
        if (empty($campaignId)) {
            return $this->notFound('mautic.campaignwatch.export.notfound');
        }
        /** @var Campaign $campaign */
        $campaign = $this->get('mautic.campaign.model.campaign')->getEntity($campaignId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'campaign:items:viewown',
            'campaign:items:viewother',
            $campaign->getCreatedBy()
        )
        ) {
            return $this->accessDenied();
        }
        // send a stream csv file of the timeline
        $name    = 'ContactsExportFrom'.str_replace(' ', '', $campaign->getName());
        $params  = $this->convertDateParams($dateFrom, $dateTo);

        $contactIds = $this->getCampaignLeadsForExport($campaignId, $params, true);
        $count      = count($contactIds);
        $start      = 0;
        $leadModel  = $this->get('mautic.lead.model.lead');

        // Edit the next two lines to tweak performance
        $params['limit'] = 1000;
        ini_set('max_execution_time', 0);

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($params, $campaignId, $contactIds, $count, $start, $leadModel) {
                $handle = fopen('php://output', 'w+');
                $headersWritten = false;
                while ($start < $count) {
                    $params['start'] = $start;
                    $contactIds      = $this->getCampaignLeadsForExport($campaignId, $params, false);
                    foreach ($contactIds as $contactId) {
                        $contact = $leadModel->getLead($contactId['lead_id']);
                        $details = $leadModel->getEntity($contactId['lead_id'])->getProfileFields();
                        $recentUtmTags    = $this->getUtmTagsByLeadId($contactId['lead_id']);
                        $dnc = $this->getDoNotContact($contactId['lead_id']);
                        $contact = array_merge($contact, end($recentUtmTags), $details, $dnc); // use only most recent UTM sources
                        // insert header row?
                        if (!$headersWritten) {
                            $headers = array_keys($contact);
                            // make sure to add dnc headers since 1st record may not have them
                            if (empty($dnc)) {
                                $dnc_headers = ['dnc_date_added', 'dnc_reason', 'dnc_channel', 'dnc_channel_id', 'dnc_comments'];
                                $headers = array_merge($headers, $dnc_headers);
                            }
                            fputcsv($handle, $headers);
                            $headersWritten = true;
                        }
                        fputcsv($handle, array_values($contact));
                        $this->container->get('doctrine.orm.entity_manager')->clear();
                        gc_enable() ;
                        gc_collect_cycles();

                    }
                    $start = $start + $params['limit'];
                }
                fclose($handle);
            }
        );
        $fileName = $name.'.csv';
        $response->setStatusCode(200);
        $response->headers->set('Content-Type', 'application/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$fileName.'"');

        return $response;
    }

    /**
     * Get leads for a specific campaign.
     *
     * @param      $campaignId
     * @param null $eventId
     *
     * @return array
     */
    public function getCampaignLeadsForExport($campaignId, $params, $countOnly)
    {
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder()
            ->from('campaign_leads', 'lc')
            ->select('lc.lead_id')
            ->leftJoin('lc', MAUTIC_TABLE_PREFIX.'leads', 'l', 'l.id = lc.lead_id');
        $q->where(
            $q->expr()->andX(
                $q->expr()->eq('lc.manually_removed', ':false'),
                $q->expr()->eq('lc.campaign_id', ':campaign')
            )
        )
            ->setParameter('false', false, 'boolean')
            ->setParameter('campaign', $campaignId);

        $q->andWhere(
            $q->expr()->andX(
                $q->expr()->gte('l.date_identified', ':dateFrom'),
                $q->expr()->lte('l.date_identified', ':dateTo')
            )
        )
            ->setParameter('dateFrom', $params['dateFrom'])
            ->setParameter('dateTo', $params['dateTo']);

        if (!$countOnly) {
            if (!empty($params['limit'])) {
                $q->setMaxResults($params['limit']);
                if (!empty($params['start'])) {
                    $q->setFirstResult($params['start']);
                }
            }
        }
        $result = $q->execute()->fetchAll();

        return $result;
    }

    private function convertDateParams($dateFrom, $dateTo)
    {
        $dateFromConverted = \DateTime::createFromFormat('M d, Y H:i:s', $dateFrom.'00:00:00')->format('Y-m-d H:i:s');
        $dateToConverted   = \DateTime::createFromFormat('M d, Y H:i:s', $dateTo.'00:00:00')->format('Y-m-d H:i:s');

        return ['dateFrom' => $dateFromConverted, 'dateTo' => $dateToConverted];
    }

    private function getUtmTagsByLeadId($contactId)
    {
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder()
            ->from('lead_utmtags', 'utm')
            ->select('utm.url AS utm_url, utm.utm_campaign, utm.utm_content, utm.utm_medium, utm.utm_source, utm.utm_term');
        $q->where(
                $q->expr()->eq('utm.lead_id', ':contact')
        )
            ->setParameter('contact', $contactId);
        $result = $q->execute()->fetchAll();

        return $result;
    }

    private function getDoNotContact($contactId)
    {
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder()
            ->from('lead_donotcontact', 'dnc')
            ->select('dnc.date_added AS dnc_dateadded, dnc.reason AS dnc_reason, dnc.channel AS dnc_channel, dnc.channel_id AS dnc_channel_id, dnc.comments AS dnc_comments');
        $q->where(
            $q->expr()->eq('dnc.lead_id', ':contact')
        )
            ->setParameter('contact', $contactId);
        $result = $q->execute()->fetchAll();

        return $result;
    }
}
