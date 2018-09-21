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
                mQuery('.shuffle-grid').shuffle();
            });
            // Add Export Button to Campaign Page Contacts Tab
            mQuery('#leads-container:first:not(.loaded)').before('<button id="lead_export_btn"><i class="fa fa-download"></i> Export</button>');
            campaignId = window.location.pathname.split('/').pop();

            mQuery('#lead_export_btn').click(function () {
                dateFrom = mQuery('#daterange_date_from:first').val();
                dateTo = mQuery('#daterange_date_to:first').val();
                $(this).after('<span id="lead-spinner"><i class="fa fa-spin fa-spinner fa-fw"></i>Please wait...</span>');
                var frame = document.createElement('iframe');
                var src = mauticBaseUrl + 's/campaignwatch/export/' + campaignId + '/' + dateFrom + '/' + dateTo;
                frame.setAttribute('src', src);
                frame.setAttribute('style', 'display: none');
                document.body.appendChild(frame);
                mQuery('#lead-spinner').remove();
            });
        }
    });
    // Hide any empty charts.
    mQuery('canvas.chart.line-chart').each(function(){
        if (mQuery(this).text().trim() === 'null') {
            mQuery(this).parent().parent().parent().parent().parent().addClass('hide');
        }
    });
</script>