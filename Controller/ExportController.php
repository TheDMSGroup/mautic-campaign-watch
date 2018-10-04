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

use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\PersistentCollection;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Mautic\LeadBundle\Entity\LeadRepository;

/**
 * Class CampaignControllerOverride.
 */
class ExportController extends CommonController
{
    /**
     * @param int    $campaignId
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|StreamedResponse
     */
    public function campaignContactsExportAction($campaignId, $dateFrom, $dateTo)
    {
        if (empty($campaignId)) {
            return $this->notFound('mautic.campaignwatch.export.notfound');
        }

        /** @var Campaign $campaign */
        $campaign = $this->getModel('campaign')->getEntity($campaignId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'campaign:items:viewown',
            'campaign:items:viewother',
            $campaign->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        // send a stream csv file of the timeline
        $name    = 'ContactsExportFrom'.str_replace(' ', '', $campaign->getName());
        list($dateFrom, $dateTo)  = $this->convertDateParams($dateFrom, $dateTo);

        $contactIds = $this->getCampaignLeadIdsForExport($campaignId, $dateFrom, $dateTo);

        // Edit the next two lines to tweak performance
        // NB: As the number of extended fields increases, the limit must be reduced to avoid memory exceptions
        $batchSize = 200;

        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->getModel('lead')->getRepository();
        $batches = array_chunk($contactIds, $batchSize);

        $response = new StreamedResponse();
        $response->setCallback(
            function () use ($contactRepo, $batches) {
                ini_set('max_execution_time', 0);

                $handle = fopen('php://output', 'w+');

                $fieldList = [];
                foreach ($batches as $batch) {
                    $contacts = $contactRepo->getEntities(['ids' => $batch, 'ignore_paginator' => true]);
                    $contacts = array_key_exists('results', $contacts) ? $contacts['results'] : $contacts;
                    /** @var Lead $contact */
                    foreach ($contacts as $contact) {
                        if (empty($fieldList)) {
                            $fieldList = $contact->getFields();
                            foreach (array_keys($fieldList) as $group) {
                                foreach($group as $field) {
                                    $fieldList = array_map(function ($f) {
                                        return $f['alias'];
                                    }, $field);
                                }
                            }
                            fputcsv($handle, $fieldList);
                        }
                        $fieldValues = array_map([$contact, 'getFieldValue'], $fieldList);
                        fputcsv($handle, $fieldValues);
                        unset($contact);
                    }
                    //fflush($handle);
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
     * @param int   $campaignId
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return mixed
     */
    public function getCampaignLeadIdsForExport($campaignId, $dateFrom, $dateTo)
    {
        /** @var QueryBuilder $q */
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from('campaign_leads', 'cl')
            ->join('cl', 'leads', 'l', 'l.id = cl.lead_id')
            ->where(
                $q->expr()->eq('cl.manually_removed', ':false'),
                $q->expr()->eq('cl.campaign_id', ':campaign'),
                $q->expr()->gte('l.date_identified', ':dateFrom'),
                $q->expr()->lt('l.date_identified', ':dateTo')
            )
            ->setParameter('false', false, \PDO::PARAM_BOOL)
            ->setParameter('campaign', $campaignId)
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo);

        $result = $q->execute()->fetchAll();
        $ids = array_map(function ($a) { return $a['lead_id']; } , $result );

        return $ids;
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    private function convertDateParams($dateFrom, $dateTo)
    {
        $timezone = new \DateTimeZone('UTC');

        $dateFromConverted = new \DateTime($dateFrom, $timezone);

        $dateToConverted   = new \DateTime($dateTo, $timezone);
        $dateToConverted->modify('+1 day');

        return [
            $dateFromConverted->format('Y-m-d H:i:s'),
            $dateToConverted->format('Y-m-d H:i:s')
        ];
    }

    /**
     * @param int $contactId
     *
     * @return mixed
     */
    private function getUtmTagsByLeadId($contactId)
    {
        /** @var QueryBuilder $q */
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

    /**
     * @param int $contactId
     *
     * @return mixed
     */
    private function getDoNotContact($contactId)
    {
        /** @var QueryBuilder $q */
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
