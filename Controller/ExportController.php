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
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Controller\CommonController;
use Mautic\LeadBundle\Controller\EntityContactsTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use MauticPlugin\MauticExtendedFieldBundle\Entity\OverrideLeadRepository;
use Symfony\Bridge\Monolog\Logger;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class CampaignControllerOverride.
 */
class ExportController extends CommonController
{
    use EntityContactsTrait;

    /** @var Logger */
    protected $logger;

    /** @var Campaign */
    protected $campaign;

    /** @var array */
    protected $contactIds = [];

    /** @var LeadRepository */
    protected $contactRepo;
    /**
     * @param int    $campaignId
     * @param string $dateFrom
     * @param string $dateTo
     *
     * @return array|\Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response|StreamedResponse
     */
    public function campaignContactsExportAction($campaignId, $dateFrom, $dateTo)
    {
        $this->logger = $this->get('logger');

        if (empty($campaignId)) {
            return $this->notFound('mautic.campaignwatch.export.notfound');
        }

        $this->campaign = $this->getModel('campaign')->getEntity($campaignId);

        if (!$this->get('mautic.security')->hasEntityAccess(
            'campaign:items:viewown',
            'campaign:items:viewother',
            $this->campaign->getCreatedBy()
        )) {
            return $this->accessDenied();
        }

        list($dateFrom, $dateTo)  = $this->convertDateParams($dateFrom, $dateTo);
        $count = $this->getCampaignLeadIdsForExport($this->campaign->getId(), $dateFrom, $dateTo);
        $this->logger->info("Found $count contacts");

        $this->contactRepo = $this->getModel('lead')->getRepository();

        $callback = [$this, 'exportContacts'];

        $fileName = sprintf('ContactsExportFrom%s.csv', str_replace(' ', '', trim($this->campaign->getName())));
        $headers = [
            'Content-Type', 'application/csv; charset=utf-8',
            'Content-Disposition', "attachment; filename='$fileName'"
        ];

        return new StreamedResponse($callback, 200, $headers);
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
            ->where(
                $q->expr()->eq('cl.manually_removed', ':false'),
                $q->expr()->eq('cl.campaign_id', ':campaign'),
                $q->expr()->gte('cl.date_added', ':dateFrom'),
                $q->expr()->lt('cl.date_added', ':dateTo')
            )
            ->setParameter('false', false, \PDO::PARAM_BOOL)
            ->setParameter('campaign', $campaignId)
            ->setParameter('dateFrom', $dateFrom)
            ->setParameter('dateTo', $dateTo);

        $result = $q->execute()->fetchAll();
        if (!empty($result)) {
            $this->contactIds = array_column($result, 'lead_id');
        }
        return count($this->contactIds);
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
     * send a stream csv file of the timeline
     */
    public function exportContacts()
    {
        // Edit the next two lines to tweak performance
        // NB: As the number of extended fields increases, the limit must be reduced to avoid memory exceptions
        ini_set('max_execution_time', 0);
        $batchSize = 50;

        $batches = array_chunk($this->contactIds, $batchSize);

        $args = [
            'filter' => [],
            'start' => 0,
            'limit' => $batchSize,
            ''
        ];

        /** @var OverrideLeadRepository $repo */
        $repo = $this->get('doctrine.orm.entity_manager')->getRepository('MauticLeadBundle:Lead');

        $repo->getEntityContacts();
        $handle = fopen('php://output', 'w+');

        $fieldList = [];
        foreach ($batches as $batch) {
            $leads = $this->contactRepo->getEntitiesWithCustomFields('lead', ['ids' => $batch]);
            $contacts = isset($leads['results']) ? $leads['results'] : $leads;
            unset($leads);

                foreach ($contacts as $id => $contact) {
                    if (empty($fieldList)) {
                        try {
                            $class = get_class($contact);
                            $fieldList = [$id, $class];
                            fputcsv($handle, $fieldList);
                        } catch (\Exception $e) {
                            fclose($handle);
                            $this->logger->error(sprintf('%s:%s: $s', __FILE__, __LINE__, $e->getMessage()));
                            return;
                        }
                        break 2;
                    }
                    $fieldValues = array_values($contact);
                    fputcsv($handle, $fieldValues);
                    unset($contact);
                }
            //fflush($handle);
        }
        fclose($handle);
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
