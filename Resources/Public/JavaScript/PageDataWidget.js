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
  'TYPO3/CMS/Plausibleio/Tabs',
], function (AjaxRequest, RegularEvent, WidgetService, Tabs) {
  'use strict';

  class PageDataWidget {
    constructor() {
      this.options = {
        dashboardItemSelector: '[data-widget-key^="plausible.pagedata"]',
        widgetContainerSelector: '[data-widget-type="pageChart"]',
        tabSelector: '[data-widget-tab-id="${tabId}"]',
        timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]',
        pageEndpoint: TYPO3.settings.ajaxUrls.plausible_pagedata
      };

      this.initialize();
    }

    requestUpdatedData(evt, chartDiv) {
      new AjaxRequest(this.options.pageEndpoint)
        .withQueryArguments({
          timeFrame: evt.detail.timeFrame,
          siteId: evt.detail.siteId,
          filter: evt.detail.filter,
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          this.renderChart(chartDiv, data);
        }).catch(error => {
            let msg = error.response ? error.response.status + ' ' + error.response.statusText : 'unknown';
            console.error('Page data controller request failed because of error: ' + msg);
          }
        );
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
        let filterBar = widget.querySelector(WidgetService.options.filterBarSelector);

        let pageChartElement = widget.querySelector(that.options.widgetContainerSelector);
        if (typeof(pageChartElement) !== 'undefined' && pageChartElement !== null) {
          widget.addEventListener('plausible:timeframechange', function (evt) {
            that.requestUpdatedData(evt, pageChartElement);
          });

          widget.addEventListener('plausible:sitechange', function (evt) {
            that.requestUpdatedData(evt, pageChartElement);
          });

          // Set filters from BE user configuration
          let dashBoardId = 'default';
          let filters = evt.detail.filters.hasOwnProperty(dashBoardId) ? evt.detail.filters[dashBoardId] : [];
          WidgetService.setFilters(filters);
          widget.addEventListener('plausible:filterchange', function (evt) {
            if (filterBar) {
              WidgetService.renderFilterBar(filterBar);
            }
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
          WidgetService.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame, WidgetService.getFilters());

          WidgetService.renderFilterBar(filterBar);

          Tabs.registerTabsForSessionHandling(widget);
        }
      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new PageDataWidget();
});
