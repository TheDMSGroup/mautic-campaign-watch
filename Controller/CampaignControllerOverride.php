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
 * Class CampaignControllerOverride
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
    public function contactsAction($objectId, $page = 1)
    {
        return $this->delegateView(
            [
                'viewParameters'  => [
                    'page'     => $page,
                    'objectId' => $objectId,
                ],
                'passthroughVars' => [
                    'route' => false,
                    'mauticContent' => null,
                ],
                'contentTemplate' => 'MauticCampaignWatchBundle:Campaign:leads.html.php',
                // 'contentTemplate' => 'MauticCampaignWatchBundle:Campaign:leads.html.php',
            ]
        );
        // return $this->render(
        //     'MauticCampaignWatchBundle:Campaign:Leads',
        //     [
        //         'events'        => null,
        //         'contactSource' => null,
        //         'tmpl'          => 'MauticCampaignWatchBundle:Campaign:leads.html.php',
        //     ]
        // );
        // @todo - see if we can avoid re-inventing the stone wheel and do the following via ajax purely?
        // //set some permissions
        // $permissions = $this->get('mautic.security')->isGranted(
        //     [
        //         'lead:leads:viewown',
        //         'lead:leads:viewother',
        //         'lead:leads:create',
        //         'lead:leads:editown',
        //         'lead:leads:editother',
        //         'lead:leads:deleteown',
        //         'lead:leads:deleteother',
        //         'lead:imports:view',
        //         'lead:imports:create',
        //     ],
        //     'RETURN_ARRAY'
        // );
        //
        // if (!$permissions['lead:leads:viewown'] && !$permissions['lead:leads:viewother']) {
        //     return $this->accessDenied();
        // }
        //
        // if ($this->request->getMethod() == 'POST') {
        //     $this->setListFilters();
        // }
        //
        // /** @var \Mautic\LeadBundle\Model\LeadModel $model */
        // $model   = $this->getModel('lead');
        // $session = $this->get('session');
        // //set limits
        // $limit = $session->get('mautic.lead.limit', $this->get('mautic.helper.core_parameters')->getParameter('default_pagelimit'));
        // $start = ($page === 1) ? 0 : (($page - 1) * $limit);
        // if ($start < 0) {
        //     $start = 0;
        // }
        //
        // //do some default filtering
        // $orderBy    = $session->get('mautic.lead.orderby', 'l.last_active');
        // $orderByDir = $session->get('mautic.lead.orderbydir', 'DESC');
        //
        // $filter      = ['force' => [
        //     // @todo - Add campaign specificity.
        // ]];
        // $translator  = $this->get('translator');
        // $anonymous   = $translator->trans('mautic.lead.lead.searchcommand.isanonymous');
        // $mine        = $translator->trans('mautic.core.searchcommand.ismine');
        // $indexMode   = $this->request->get('view', $session->get('mautic.lead.indexmode', 'list'));
        //
        // $session->set('mautic.lead.indexmode', $indexMode);
        //
        // $filter['force'] .= " !$anonymous";
        //
        // if (!$permissions['lead:leads:viewother']) {
        //     $filter['force'] .= " $mine";
        // }
        //
        // $results = $model->getEntities([
        //     'start'          => $start,
        //     'limit'          => $limit,
        //     'filter'         => $filter,
        //     'orderBy'        => $orderBy,
        //     'orderByDir'     => $orderByDir,
        //     'withTotalCount' => true,
        // ]);
        //
        // $count = $results['count'];
        // unset($results['count']);
        //
        // $leads = $results['results'];
        // unset($results);
        //
        // if ($count && $count < ($start + 1)) {
        //     //the number of entities are now less then the current page so redirect to the last page
        //     if ($count === 1) {
        //         $lastPage = 1;
        //     } else {
        //         $lastPage = (ceil($count / $limit)) ?: 1;
        //     }
        //     $session->set('mautic.lead.page', $lastPage);
        //     $returnUrl = $this->generateUrl('mautic_contact_index', ['page' => $lastPage]);
        //
        //     return $this->postActionRedirect(
        //         [
        //             'returnUrl'       => $returnUrl,
        //             'viewParameters'  => ['page' => $lastPage],
        //             'contentTemplate' => 'MauticLeadBundle:Lead:index',
        //             'passthroughVars' => [
        //                 'activeLink'    => '#mautic_contact_index',
        //                 'mauticContent' => 'lead',
        //             ],
        //         ]
        //     );
        // }
        //
        // //set what page currently on so that we can return here after form submission/cancellation
        // $session->set('mautic.lead.page', $page);
        //
        // $tmpl = $this->request->isXmlHttpRequest() ? $this->request->get('tmpl', 'index') : 'index';
        //
        // $listArgs = [];
        // if (!$this->get('mautic.security')->isGranted('lead:lists:viewother')) {
        //     $listArgs['filter']['force'] = " $mine";
        // }
        //
        // // Get the max ID of the latest lead added
        // $maxLeadId = $model->getRepository()->getMaxLeadId();
        //
        // return $this->delegateView(
        //     [
        //         'viewParameters' => [
        //             'searchValue'      => $search,
        //             'items'            => $leads,
        //             'page'             => $page,
        //             'totalItems'       => $count,
        //             'limit'            => $limit,
        //             'permissions'      => $permissions,
        //             'tmpl'             => $tmpl,
        //             'indexMode'        => $indexMode,
        //             'lists'            => [],
        //             'currentList'      => null,
        //             'security'         => $this->get('mautic.security'),
        //             'inSingleList'     => false,
        //             'noContactList'    => [],
        //             'maxLeadId'        => $maxLeadId,
        //             'anonymousShowing' => false,
        //         ],
        //         'contentTemplate' => "MauticLeadBundle:Lead:{$indexMode}.html.php",
        //         'passthroughVars' => [
        //             'activeLink'    => '#mautic_contact_index',
        //             'mauticContent' => 'lead',
        //             'route'         => $this->generateUrl('mautic_contact_index', ['page' => $page]),
        //         ],
        //     ]
        // );
    }
}
