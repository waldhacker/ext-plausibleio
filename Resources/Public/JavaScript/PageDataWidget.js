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
  'TYPO3/CMS/Plausibleio/WidgetService',
], function (AjaxRequest, RegularEvent, WidgetService) {

  class PageDataWidget {
    constructor() {
      this.options = {
        dashboardItemSelector: '.dashboard-item',
        widgetContainerSelector: '[data-widget-type="pageChart"]',
        tabSelector: '[data-widget-tab-id="${tabId}"]',
        timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
        pageEndpoint: TYPO3.settings.ajaxUrls.plausible_pagedata,
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
      let that = this;

      if (chartDiv && data && data.length) {
        data.forEach(function (tabData) {
          let tab = chartDiv.querySelector(that.options.tabSelector.replace('${tabId}', tabData.tab));
          if (tab) {
            WidgetService.renderBarChart(tab, tabData.data, true);
          }
        });
      }
    }
    
    initialize() {
      let that = this;
      new RegularEvent('widgetContentRendered', function (e) {
        e.preventDefault();
        let widget = e.target;

        let pageChartElement = widget.querySelector(that.options.widgetContainerSelector);
        if (pageChartElement) {
          widget.addEventListener('plausible:timeframechange', function (evt) {
            that.requestUpdatedData(evt, pageChartElement);
          });

          let timeFrameSelect = e.target.querySelector(that.options.timeframeSelectSelector);
          WidgetService.registerTimeSelector(timeFrameSelect);

          // request and render data
          WidgetService.dispatchTimeFrameChange(widget, timeFrameSelect.value);
        }
      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new PageDataWidget();
});
