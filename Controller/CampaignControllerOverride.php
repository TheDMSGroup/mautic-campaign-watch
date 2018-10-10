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

use Mautic\CampaignBundle\Controller\CampaignController;

/**
 * Class CampaignControllerOverride.
 */
class CampaignControllerOverride extends CampaignController
{
    /**
     * Alternative of 'MauticCampaignBundle:Campaign:contacts'
     * Gets contacts as a list.
     *
     * @param     $objectId
     * @param int $page
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    // public function contactsAction($objectId, $page = 1, $count = NULL, \DateTime $dateFrom = null, \DateTime $dateTo = null)
    // {
    //     return $this->delegateView(
    //         [
    //             'viewParameters'  => [
    //                 'page'     => $page,
    //                 'objectId' => $objectId,
    //                 'count'    => $count,
    //                 'dateFrom' => $dateFrom,
    //                 'dateTo'   => $dateTo,
    //             ],
    //             'passthroughVars' => [
    //                 'route'         => false,
    //                 'mauticContent' => null,
    //             ],
    //             'contentTemplate' => 'MauticCampaignWatchBundle:Campaign:leads.html.php',
    //         ]
    //     );
    // }

}
