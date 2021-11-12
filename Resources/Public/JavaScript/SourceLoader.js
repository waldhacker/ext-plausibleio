/*
 * This file is part of the plausibleio extension for TYPO3
 * - (c) 2021 waldhacker UG (haftungsbeschränkt)
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

define([
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'lit',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format',
  'TYPO3/CMS/Plausibleio/PlausibleWidgets',
], function (AjaxRequest, RegularEvent, lit, D3Format, PW) {

  class SourceLoader {
    constructor() {
      this.options = {
        dashboardItemSelector: '[data-widget-key="plausible.sourcedata"]',
        widgetContentSelector: '.widget-content',
        pageEndpoint: TYPO3.settings.ajaxUrls.plausible_source,
      };

      this.initialize();
    }

    requestUpdatedData(evt, chartDiv) {
      new AjaxRequest(this.options.pageEndpoint)
        .withQueryArguments({
          timeFrame: evt.detail.timeFrame
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          this.renderChart(chartDiv, data);
        });
    }

    renderChart(chartDiv, data) {
      if (chartDiv && data && data.length) {
        data.forEach(function (tabData) {
          let tab = chartDiv.querySelector('[data-widget-type="' + tabData.tab + '"]');
          if (tab)
            PW.renderBarChart(tab, tabData.data, true);
        });
      }
    }

    initialize() {
      let that = this;

      new RegularEvent('widgetContentRendered', function (e) {
        e.preventDefault();
        let widget = e.target;

        let pageChartElement = widget.querySelector('[data-widget-type="sourceChart"]');
        if (pageChartElement) {
          widget.addEventListener('timeframechange', function (evt) {
            that.requestUpdatedData(evt, pageChartElement);
          });

          let timeFrameSelect = e.target.querySelector('[data-widget-type="plausible-timeframe"]');
          PW.registerTimeSelector(timeFrameSelect);

          // request and render data
          PW.dispatchTimeFrameChange(widget, timeFrameSelect.value);
        }

      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new SourceLoader();
});
