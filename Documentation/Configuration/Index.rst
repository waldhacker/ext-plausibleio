.. include:: ../Includes.txt

.. _configuration:

=============
Configuration
=============

Widget Configuration
====================

The extension is configured via the extension settings in "Admin Tools" > "Settings".
The following settings are available:

.. code-block:: typoscript

   # Plausible Base URL: If you are using a self-hosted plausible instance, add your domain here (https://<your-custom-domain>/)
   baseUrl = https://plausible.io

   # API Key: You must set a token here - generate on in your Plausible instance -> User Settings -> API keys
   apiKey =

   # Sites: Enter your Plausible site ID (domains) here - if you have more than one, use a default one here and add multiple widget configurations with different sites via services.yaml
   siteId = waldhacker.dev

   # Time Frames: Comma-separated list of available time frames - see https://plausible.io/docs/stats-api#time-periods for possible options ("custom" is currently not available)
   timeFrames = day,7d,30d,month,6mo,12mo

   # Default Time Frame: The time frame to use initially when rendering the widgets
   defaultTimeFrame = 30d

Advanced Configuration
----------------------

To further customize widgets, use your :file:`Services.yaml` file in your sitepackage and
register new widgets. This allows you to register widgets for multiple sites or timeframes.

Example:

.. code-block:: yaml

  yourname.plausibleio.widget.device:
    class: 'Waldhacker\Plausibleio\Dashboard\Widget\DeviceDataWidget'
    arguments:
      $dataProvider: '@Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider'
      $view: '@dashboard.views.widget'
      $configurationService: '@Waldhacker\Plausibleio\Services\ConfigurationService'
      $options:
        siteId: another-site.dev
        timeFrame: 30d
    tags:
      - name: dashboard.widget
        identifier: 'yourname.devicedata'
        groupNames: 'plausibleio'
        title: 'LLL:EXT:yourpackage/Resources/Private/Language/locallang.xlf:widgets.deviceData.label'
        description: 'LLL:EXT:yourpackage/Resources/Private/Language/locallang.xlf:widgets.deviceData.description'
        iconIdentifier: 'content-widget-chart-bar'
        height: 'medium'
        width: 'medium'


Integrating Plausible Analytics in the frontend
===============================================

This extension does not provide any frontend integration for plausible. Since TYPO3 v10 you can use the TYPO3 AssetCollector to add the analytics script to your website directly in your templates, no extension necessary - for example:

.. code-block:: html

   <f:asset.script identifier="analytics" src="https://plausible.io/yoursite/js/plausible.js" data="{'domain':'example.com'}" priority="1" async="async" defer="defer" />

