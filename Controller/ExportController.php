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
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
        $start = 0;
        $limit = 1000;

        if (empty($campaignId)) {
            return $this->notFound('mautic.campaignwatch.export.notfound');
        }

        $campaignId = (int) $campaignId;
        $campaign   = $this->getModel('campaign')->getEntity($campaignId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'campaign:campaigns:viewown',
            'campaign:campaigns:viewother',
            $campaign->getCreatedBy()
        )) {
            return $this->accessDenied();
        }
        $entityManager = $this->get('doctrine.orm.entity_manager');

        list($dateFrom, $dateTo) = $this->convertDateParams($dateFrom, $dateTo);
        $contactIds              = $this->getCampaignLeadIdsForExport($campaignId, $dateFrom, $dateTo, $start, $limit);

        if (empty($contactIds)) {
            return $this->notFound('mautic.campaignwatch.export.nocontacts');
        }

        //testing
        /** @var OverrideLeadRepository $contactRepo */
        $contactRepo = $this->getModel('lead')->getRepository();

        $fileName = sprintf('ContactsExportFrom%s.csv', str_replace(' ', '', $campaign->getName()));

        $response = new StreamedResponse(
            function () use (
                $contactRepo,
                $entityManager,
                $campaignId,
                $dateFrom,
                $dateTo,
                $start,
                $limit,
                $contactIds
            ) {
                ini_set('max_execution_time', 0);
                $handle = fopen('php://output', 'w');

                while (count($contactIds)) {
                    $fieldNames = [];
                    // adjust $size for memory vs. speed
                    $batches = array_chunk($contactIds, 100);
                    foreach ($batches as $batch) {
                        //$leads = $contactRepo->getEntitiesWithCustomFields('lead', ['ids' => $batch]);
                        /** @var OverrideLeadRepository $contactRepo */
                        $leads = $contactRepo->getEntities(
                            [
                                'ids'              => $batch,
                                '',
                                'withTotalCounts'  => 0,
                                'withChannelRules' => 1,
                                'ignore_paginator' => 1,
                            ]
                        );

                        $utmValues = $this->getUtmTagsByLeadIds($batch);
                        $utmFields = [
                            'utm_url'      => 'UTM URL',
                            'utm_campaign' => 'UTM Campaign',
                            'utm_content'  => 'UTM Content',
                            'utm_medium'   => 'UTM Medium',
                            'utm_source'   => 'UTM Source',
                            'utm_term'     => 'UTM Term',
                        ];

                        $deviceValues = $this->getDeviceDataByLeadIds($batch);
                        $deviceFields = [
                            'device'              => 'Device',
                            'device_os_name'      => 'Device OS Name',
                            'device_os_shortname' => 'Device OS Shortname',
                            'device_os_version'   => 'Device OS Version',
                            'device_os_platform'  => 'Device OS Platform',
                            'device_brand'        => 'Device Brand',
                            'device_model'        => 'Device Model',
                            'device_fingerprint'  => 'Device Fingerprint',
                            'tracking_id'         => 'Tracking ID',
                        ];

                        /**
                         * @var int
                         * @var Lead $lead
                         */
                        foreach ($leads as $id => $lead) {
                            if (empty($fieldNames)) {
                                $fields      = $lead->getFields(true);
                                $columnNames = array_map(
                                    function ($f) {
                                        return $f['label'];
                                    },
                                    $fields
                                );
                                $columnNames = array_merge(['Id', 'IP'], $columnNames);
                                $columnNames = array_merge($columnNames, $utmFields);
                                $columnNames = array_merge($columnNames, $deviceFields);

                                fputcsv($handle, $columnNames);
                                $fieldNames = array_map(
                                    function ($f) {
                                        return $f['alias'];
                                    },
                                    $fields
                                );
                            }
                            $ipAddresses = $lead->getIpAddresses()->last();
                            $ip = !$ipAddresses ? '' : $ipAddresses->getIpAddress();
                            $values = [$id, $ip];
                            foreach ($fieldNames as $fieldName) {
                                $values[] = $lead->getFieldValue($fieldName);
                            }

                            $addExtras = function ($data, $dataFields) use (&$values, &$lead) {
                                foreach ($data as $row) {
                                    if (isset($row['lead_id']) && $row['lead_id'] == $lead->getId()) {
                                        foreach ($row as $k => $val) {
                                            if ('lead_id' !== $k) {
                                                $values[] = $val;
                                            }
                                        }

                                        return;
                                    }
                                }
                                // If the lead doesn't have that row, then just
                                // add blanks to fill in.
                                for ($i=0; $i < count($dataFields); ++$i) {
                                    $values[] = '';
                                }
                            };
                            $addExtras($utmValues, $utmFields);
                            $addExtras($deviceValues, $deviceFields);

                            fputcsv($handle, $values);
                        }
                        $entityManager->flush();
                        $entityManager->clear();
                    }
                    $start += count($contactIds);
                    $contactIds = $this->getCampaignLeadIdsForExport(
                        $campaignId,
                        $dateFrom,
                        $dateTo,
                        $start,
                        $limit
                    );
                }

                fclose($handle);
            },
            200,
            [
                'Content-Type'        => 'application/csv; charset=utf-8',
                'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
            ]
        );

        return $response;
    }

    private function getDeviceDataByLeadIds($contactIds)
    {
        /** @var QueryBuilder $q */
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder()
                  ->select([
                      'lead_id',
                      'device',
                      'device_os_name',
                      'device_os_shortname',
                      'device_os_version',
                      'device_os_platform',
                      'device_brand',
                      'device_model',
                      'device_fingerprint',
                      'tracking_id',
                  ])
            ->from('lead_devices', 'd');
        $q->where($q->expr()->in('d.lead_id', $contactIds));

        $result = $q->execute()->fetchAll();

        return $result;
    }

    /**
     * @param array $contactId
     *
     * @return mixed
     */
    private function getUtmTagsByLeadIds($contactIds)
    {
        /** @var QueryBuilder $q */
        $q = $this->container->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder()
            ->from('lead_utmtags', 'utm')
            ->select(
                'lead_id,
                 utm.url AS utm_url,
                 utm.utm_campaign,
                 utm.utm_content,
                 utm.utm_medium,
                 utm.utm_source,
                 utm.utm_term '
            );
        $q->where($q->expr()->in('utm.lead_id', $contactIds));

        $result = $q->execute()->fetchAll();

        return $result;
    }

    /**
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array
     */
    private function convertDateParams($dateFrom, $dateTo)
    {
        $dateFromConverted = new \DateTime($dateFrom);

        $dateToConverted = new \DateTime($dateTo);
        $dateToConverted->modify('+1 day -1 second');

        return [
            $dateFromConverted->getTimestamp(),
            $dateToConverted->getTimestamp(),
        ];
    }

    /**
     * @param     $campaignId
     * @param     $dateFrom
     * @param     $dateTo
     * @param int $first
     * @param int $limit
     *
     * @return array
     */
    public function getCampaignLeadIdsForExport($campaignId, $dateFrom, $dateTo, $first = 0, $limit = 1000)
    {
        $contactIds = [];
        /** @var QueryBuilder $q */
        $q = $this->get('doctrine.orm.entity_manager')->getConnection()->createQueryBuilder();
        $q->select('cl.lead_id')
            ->from('campaign_leads', 'cl')
            ->where(
                $q->expr()->eq('cl.manually_removed', ':false'),
                $q->expr()->eq('cl.campaign_id', ':campaign'),
                $q->expr()->gte('cl.date_added', 'FROM_UNIXTIME(:dateFrom)'),
                $q->expr()->lt('cl.date_added', 'FROM_UNIXTIME(:dateTo)')
            )
            ->setParameter('false', false, 'boolean')
            ->setParameter('campaign', $campaignId)
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo)
            ->setFirstResult($first)
            ->setMaxResults($limit);

        $result = $q->execute()->fetchAll();
        if (!empty($result)) {
            $contactIds = array_column($result, 'lead_id');
        }

        return $contactIds;
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
            ->select(
                'dnc.date_added AS dnc_dateadded, dnc.reason AS dnc_reason, dnc.channel AS dnc_channel, dnc.channel_id AS dnc_channel_id, dnc.comments AS dnc_comments'
            );
        $q->where(
            $q->expr()->eq('dnc.lead_id', ':contact')
        )
            ->setParameter('contact', $contactId);
        $result = $q->execute()->fetchAll();

        return $result;
    }
}
