# Mautic Health [![Latest Stable Version](https://poser.pugx.org/thedmsgroup/mautic-health-bundle/v/stable)](https://packagist.org/packages/thedmsgroup/mautic-health-bundle) [![License](https://poser.pugx.org/thedmsgroup/mautic-health-bundle/license)](https://packagist.org/packages/thedmsgroup/mautic-health-bundle) [![Build Status](https://travis-ci.org/TheDMSGroup/mautic-health.svg?branch=master)](https://travis-ci.org/TheDMSGroup/mautic-health)
![](./Assets/img/health.png)

Checks the health of the Mautic instance, optionally reporting to statuspage.io.

This is currently used to find out if certain cron jobs are overdue or overtaxed.
There are likely many other things that can be checked to discern the health of Mautic.
For example, RAM, hard drive utilization, CPU, connectivity, email spool size.
Suggestions? Let us know. PRs are also welcome.

Checks currently included:
* mautic:campaigns:update
* mautic:campaigns:trigger

## Installation & Usage

Currently being tested with Mautic `2.14.x`.
If you have success/issues with other versions please report.

1. Install by running `composer require thedmsgroup/mautic-health-bundle`
   (or by extracting this repo to `/plugins/MauticHealthBundle`)
2. Go to `/s/plugins/reload`
3. Click "Health" and configure as desired.
4. Add the cron task to run the health check as often as desired (Every 15 minutes recommended):
   `5,20,35,50 * * * * php /path/to/mautic/app/console mautic:health:check`