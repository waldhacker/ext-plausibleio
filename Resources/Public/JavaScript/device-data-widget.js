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

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import RegularEvent from '@typo3/core/event/regular-event.js';
import WidgetService from '@waldhacker/plausibleio/widget-service.js';
import Tabs from '@waldhacker/plausibleio/tabs.js';

class DeviceDataWidget {
  constructor() {
    this.options = {
      dashboardItemSelector: '[data-widget-key^="plausible.devicedata"]',
      widgetContainerSelector: '[data-widget-type="deviceChart"]',
      tabSelector: '[data-widget-tab-id="${tabId}"]',
      timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
      siteSelector: '[data-widget-plausible-sites-select]',
      pageEndpoint: TYPO3.settings.ajaxUrls.plausible_devicedata
    };

    this.initialize();
  }

  requestUpdatedData(evt, chartDiv) {
    new AjaxRequest(this.options.pageEndpoint)
      .withQueryArguments({
        timeFrame: evt.detail.timeFrame,
        siteId: evt.detail.siteId
      })
      .get()
      .then(async (response) => {
        const data = await response.resolve();
        this.renderChart(chartDiv, data);
      });
  }

  renderChart(chartDiv, data) {
    let that = this;

    if (typeof(chartDiv) !== 'undefined' && chartDiv !== null && data && data.length > 0) {
      data.forEach(function (tabData) {
        let tab = chartDiv.querySelector(that.options.tabSelector.replace('${tabId}', tabData.tab));
        if (typeof(tab) !== 'undefined' && tab !== null) {
          WidgetService.renderBarChart(tab, tabData.data, true);
        }
      });
    }
  }

  initialize() {
    let that = this;

    new RegularEvent('widgetContentRendered', function (evt) {
      evt.preventDefault();
      let widget = evt.target;

      let pageChartElement = widget.querySelector(that.options.widgetContainerSelector);
      if (typeof(pageChartElement) !== 'undefined' && pageChartElement !== null) {
        widget.addEventListener('plausible:timeframechange', function (evt) {
          that.requestUpdatedData(evt, pageChartElement);
        });

        widget.addEventListener('plausible:sitechange', function (evt) {
          that.requestUpdatedData(evt, pageChartElement);
        });

        let timeFrameSelect = widget.querySelector(that.options.timeframeSelectSelector);
        if (typeof(timeFrameSelect) !== 'undefined' && timeFrameSelect !== null) {
          WidgetService.registerTimeSelector(timeFrameSelect);
        }

        let siteSelect = widget.querySelector(that.options.siteSelector);
        if (typeof(siteSelect) !== 'undefined' && siteSelect !== null) {
          WidgetService.registerSiteSelector(siteSelect);
        }

        // request and render data
        let configuration = WidgetService.getSiteAndTimeFrameFromDashboardItem(widget);
        WidgetService.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame);

        Tabs.registerTabsForSessionHandling(widget);
      }
    }).delegateTo(document, this.options.dashboardItemSelector);
  }
}

export default new DeviceDataWidget();
