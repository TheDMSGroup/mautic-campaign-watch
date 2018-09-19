<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html

 */

$view->extend('MauticCoreBundle:Default:content.html.php');
$view['slots']->set('headerTitle', $campaign->getName());

$view['slots']->set(
    'actions',
    $view->render(
        'MauticCoreBundle:Helper:page_actions.html.php',
        [
            'item'            => $campaign,
            'templateButtons' => [
                'edit'   => $permissions['campaign:campaigns:edit'],
                'clone'  => $permissions['campaign:campaigns:create'],
                'delete' => $permissions['campaign:campaigns:delete'],
                'close'  => $permissions['campaign:campaigns:view'],
            ],
            'routeBase' => 'campaign',
        ]
    )
);
$view['slots']->set(
    'publishStatus',
    $view->render('MauticCoreBundle:Helper:publishstatus_badge.html.php', ['entity' => $campaign])
);

$campaignId = $campaign->getId();

$preview = trim($view->render('MauticCampaignBundle:Campaign:preview.html.php', [
    'campaignId'      => $campaignId,
    'campaign'        => $campaign,
    'campaignEvents'  => $campaignEvents,
    'campaignSources' => $campaignSources,
    'eventSettings'   => $eventSettings,
    'canvasSettings'  => $campaign->getCanvasSettings(),
]));

$decisions  = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['decision']]));
$actions    = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['action']]));
$conditions = trim($view->render('MauticCampaignBundle:Campaign:events.html.php', ['events' => $events['condition']]));

switch (true) {
    case !empty($preview):
        $firstTab = 'preview';
        break;
    case !empty($decisions):
        $firstTab = 'decision';
        break;
    case !empty($actions):
        $firstTab = 'action';
        break;
    case !empty($conditions):
        $firstTab = 'condition';
        break;
}
?>

<!-- start: box layout -->
<div class="box-layout">
    <!-- left section -->
    <div class="col-md-9 bg-white height-auto">
        <div class="bg-auto">
            <!-- campaign detail header -->
            <div class="pr-md pl-md pt-lg pb-lg">
                <div class="box-layout">
                    <div class="col-xs-6 va-m">
                        <div class="text-white dark-sm mb-0"><?php echo $campaign->getDescription(); ?></div>
                    </div>
                </div>
            </div>
            <!--/ campaign detail header -->

            <!-- campaign detail collapseable -->
            <div class="collapse" id="campaign-details">
                <div class="pr-md pl-md pb-md">
                    <div class="panel shd-none mb-0">
                        <table class="table table-bordered table-striped mb-0">
                            <tbody>
                            <?php echo $view->render(
                                'MauticCoreBundle:Helper:details.html.php',
                                ['entity' => $campaign]
                            ); ?>
                            <?php if (!empty($tags)): ?>
                                <tr>
                                    <td width="20%"><span class="fw-b">
                                    <?php echo $view['translator']->trans('mautic.campaign.campaign.tags'); ?>
                                    </td>
                                    <td>
                                        <?php echo implode(', ', array_map(function ($ele) { return $ele['label']; }, $tags)); ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                            <?php foreach ($sources as $sourceType => $typeNames): ?>
                            <?php if (!empty($typeNames)): ?>
                            <tr>
                                <td width="20%"><span class="fw-b">
                                    <?php echo $view['translator']->trans('mautic.campaign.leadsource.'.$sourceType); ?>
                                </td>
                                <td>
                                    <?php echo implode(', ', $typeNames); ?>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <!--/ campaign detail collapseable -->
        </div>

        <div class="bg-auto bg-dark-xs">
            <!-- campaign detail collapseable toggler -->
            <div class="hr-expand nm">
                <span data-toggle="tooltip" title="Detail">
                    <a href="javascript:void(0)" class="arrow text-muted collapsed" data-toggle="collapse"
                       data-target="#campaign-details"><span
                            class="caret"></span> <?php echo $view['translator']->trans('mautic.core.details'); ?></a>
                </span>
            </div>
            <!--/ campaign detail collapseable toggler -->

            <?php echo $view['content']->getCustomContent('left.section.top', $mauticTemplateVars); ?>

            <!-- some stats -->
            <!--/ stats -->

            <?php echo $view['content']->getCustomContent('details.stats.graph.below', $mauticTemplateVars); ?>

            <!-- tabs controls -->
            <ul class="nav nav-tabs pr-md pl-md">
                <?php if ($preview): ?>
                     <li class="<?php if ('preview' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#preview-container" role="tab" data-toggle="tab">
                            <?php echo $view['translator']->trans('mautic.campaign.preview.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($decisions): ?>
                    <li class="<?php if ('decision' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#decisions-container" role="tab" data-toggle="tab" data-eventtype="decision">
                            <?php echo $view['translator']->trans('mautic.campaign.event.decisions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($actions): ?>
                    <li class="<?php if ('action' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#actions-container" role="tab" data-toggle="tab" data-eventtype="action">
                            <?php echo $view['translator']->trans('mautic.campaign.event.actions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <?php if ($conditions): ?>
                    <li class="<?php if ('condition' == $firstTab): echo 'active'; endif; ?>">
                        <a href="#conditions-container" role="tab" data-toggle="tab" data-eventtype="condition">
                            <?php echo $view['translator']->trans('mautic.campaign.event.conditions.header'); ?>
                        </a>
                    </li>
                <?php endif; ?>
                <li class="">
                    <a href="#leads-container" role="tab" data-toggle="tab">
                        <?php echo $view['translator']->trans('mautic.lead.leads'); ?>
                    </a>
                </li>
                <?php echo $view['content']->getCustomContent('tabs', $mauticTemplateVars); ?>
            </ul>
            <!--/ tabs controls -->
        </div>

        <!-- start: tab-content -->
        <div class="tab-content pa-md">
            <!-- #events-container -->
            <!-- BEGIN toggle view type -->
            <?php
               $hideDataToggle = '';
                if (!in_array($firstTab, ['action', 'condition', 'decision'])) {
                    $hideDataToggle = " style='display:none; '";
                }
            ?>
            <div id="campaignTabDataToggle" class="form-group" <?php echo $hideDataToggle; ?>>
                <label class="control-label">Show Results As</label>
                <div class="choice-wrapper">
                    <div class="btn-group btn-block" data-toggle="buttons">
                        <label class="btn btn-default  btn-yes <?php echo $tabDataMode['byDate']['class']; ?>">
                            <input type="radio" name="tabDataMode"
                                   data-mode="byDate"
                                   data-campaignid = "<?php echo $campaignId; ?>"
                                   style="width: 1px; height: 1px; top: 0; left: 0; margin-top: 0;" autocomplete="false"
                                   value="0" <?php echo $tabDataMode['byDate']['checked']; ?>> <span><?php echo $view['translator']->trans('mautic.campaign.tabcontenttoggle.bydate'); ?></span>
                        </label>
                        <label class="btn btn-default active btn-yes <?php echo $tabDataMode['toDate']['class']; ?>">
                            <input type="radio" name="tabDataMode"
                                   data-mode="toDate"
                                   data-campaignid = "<?php echo $campaignId; ?>"
                                   style="width: 1px; height: 1px; top: 0; left: 0; margin-top: 0;" autocomplete="false"
                                   value="1" <?php echo $tabDataMode['toDate']['checked']; ?>> <span><?php echo $view['translator']->trans('mautic.campaign.tabcontenttoggle.todate'); ?></span>
                        </label>
                    </div>
                </div>
            </div>
            <!-- END toggle view type -->
            <?php if ($preview): ?>
                <div class="<?php if ('preview' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="preview-container">
                    <?php echo $preview; ?>
                </div>
            <?php endif; ?>
            <?php if ($decisions): ?>
                <div class="<?php if ('decision' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="decisions-container">
                    <?php echo $decisions; ?>
                </div>
            <?php endif; ?>
            <?php if ($actions): ?>
                <!-- END toggle view type -->
                <div class="<?php if ('action' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="actions-container">
                    <?php echo $actions; ?>
                </div>
            <?php endif; ?>
            <?php if ($conditions): ?>
                <!-- END toggle view type -->
                <div class="<?php if ('condition' == $firstTab): echo 'active '; endif; ?> tab-pane fade in bdr-w-0" id="conditions-container">
                    <?php echo $conditions; ?>
                </div>
            <?php endif; ?>
            <!--/ #events-container -->
            <div class="tab-pane fade in bdr-w-0 page-list" id="leads-container">
                <?php echo $campaignLeads; ?>
                <div class="clearfix"></div>
            </div>
            <?php echo $view['content']->getCustomContent('tabs.content', $mauticTemplateVars); ?>
        </div>
        <!--/ end: tab-content -->

        <?php echo $view['content']->getCustomContent('left.section.bottom', $mauticTemplateVars); ?>
    </div>
    <!--/ left section -->

    <!-- right section -->
    <div class="col-md-3 bg-white bdr-l height-auto">
        <?php echo $view['content']->getCustomContent('right.section.top', $mauticTemplateVars); ?>
        <?php echo $view->render('MauticCoreBundle:Helper:recentactivity.html.php', ['logs' => $logs]); ?>
        <?php echo $view['content']->getCustomContent('right.section.bottom', $mauticTemplateVars); ?>
    </div>
    <!--/ right section -->
</div>
<!--/ end: box layout -->
