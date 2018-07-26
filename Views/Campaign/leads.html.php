<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Digital Media Solutions, LLC
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<i class="fa fa-spin fa-spinner fa-fw"></i>Please wait...
<script type="text/javascript">
    mQuery('a[href="#leads-container"]:first:not(.loaded)').click(function () {
        if (!mQuery(this).hasClass('loaded')) {
            mQuery(this).addClass('loaded');
            Mautic.loadStylesheet(mauticBaseUrl + 'plugins/MauticCampaignWatchBundle/Assets/css/leads.css');
            Mautic.loadContent(mauticBaseUrl + 's/campaigns/view/<?php echo $objectId; ?>/contact/1?limit=100', '', 'GET', '#leads-container', true, function () {
                mQuery('.shuffle-grid').shuffle('update');
            });
        }
    });
</script>