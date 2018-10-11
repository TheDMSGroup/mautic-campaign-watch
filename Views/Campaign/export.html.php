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
<script type="text/javascript">
    mQuery('a[href="#leads-container"]:first')
        .parent()
        .after('<button id="lead_export_btn" class="btn btn-success pull-right"><i class="fa fa-download"></i> Export Contacts</button>')
        .parent()
        .find('#lead_export_btn')
        .click(function () {
            console.log('dnloadin');
            dateFrom = mQuery('#daterange_date_from:first').val();
            dateTo = mQuery('#daterange_date_to:first').val();
            var frame = document.createElement('iframe'),
                campaignId = window.location.pathname.split('/').pop(),
                src = mauticBaseUrl + 's/campaignwatch/export/' + campaignId + '/' + dateFrom + '/' + dateTo,
                button = mQuery(this);
            frame.setAttribute('src', src);
            frame.setAttribute('style', 'display: none');
            document.body.appendChild(frame);
            button.attr('disabled', 'disabled');
            setTimeout(function () {
                button.attr('disabled', false);
                button.find('i').removeClass('fa-spin').removeClass('fa-spinner').addClass('fa-download');
            }, 30000);
        });
    Mautic.loadStylesheet(mauticBaseUrl + 'plugins/MauticCampaignWatchBundle/Assets/css/leads.css');
</script>