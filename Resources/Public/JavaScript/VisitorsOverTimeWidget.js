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

var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
define([
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'TYPO3/CMS/Dashboard/Contrib/chartjs',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format',
  'TYPO3/CMS/Plausibleio/WidgetService',
], function (AjaxRequest, RegularEvent, chartjs_1, D3Format, WidgetService) {
  'use strict';
  chartjs_1 = __importDefault(chartjs_1);

  class VisitorsOverTimeWidget {
    constructor() {
      this.options = {
        dashboardItemSelector: '[data-widget-key^="plausible.visitorsovertime"]',
        widgetContainerSelector: '[data-widget-type="visitorsChart"]',
        timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]',
        uniqueVisitorsOverviewItemSelector: '[data-widget-chart-overview-item="uniqueVisitors"]',
        totalPageviewsOverviewItemSelector: '[data-widget-chart-overview-item="totalPageviews"]',
        currentVisitorsOverviewItemSelector: '[data-widget-chart-overview-item="currentVisitors"]',
        visitDurationOverviewItemSelector: '[data-widget-chart-overview-item="visitDuration"]',
        visitorTimeSeriesEndpoint: TYPO3.settings.ajaxUrls.plausible_visitorsovertime
      };
      this.initialize();
    }

    requestUpdatedData(evt, widget, chart) {
      new AjaxRequest(this.options.visitorTimeSeriesEndpoint).withQueryArguments({
        timeFrame: evt.detail.timeFrame,
        siteId: evt.detail.siteId
      })
      .get()
      .then(async (response) => {
        const data = await response.resolve();
        if (chart && data) {
          chart.data.labels = data.chartData.labels;
          chart.data.datasets = data.chartData.datasets;
          chart.update();
        }

        this.renderOverviewData(widget, data.overViewData);
      });
    }

    initialize() {
      let that = this;
      new RegularEvent('widgetContentRendered', function (evt) {
        evt.preventDefault();

        if(!evt.target.querySelector(that.options.widgetContainerSelector)) {
          return;
        }

        let visitorsWidgetChart = null;
        chartjs_1.default.helpers.each(chartjs_1.default.instances, function (instance) {
          const widgetKey = instance.canvas.closest(that.options.dashboardItemSelector).dataset.widgetKey;
          if (widgetKey.indexOf('plausible.visitorsovertime', 0) === 0) {
            visitorsWidgetChart = instance;
          }
        });

        if (!visitorsWidgetChart) {
          return;
        }

        let widget = visitorsWidgetChart.canvas.closest(that.options.dashboardItemSelector);

        widget.addEventListener('plausible:timeframechange', function (evt) {
          that.requestUpdatedData(evt, widget, visitorsWidgetChart);
        });

        widget.addEventListener('plausible:sitechange', function (evt) {
          that.requestUpdatedData(evt, widget, visitorsWidgetChart);
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
      }).delegateTo(document, this.options.dashboardItemSelector);
    }

    formatSIPrefix(n) {
      // 2400 -> 2.4k
      n = D3Format.format('.2~s')(n);
      return n;
    }

    renderOverviewData(widget, data) {
      if (typeof(widget) !== 'undefined' && widget !== null && data) {
        widget.querySelector(this.options.uniqueVisitorsOverviewItemSelector).innerHTML = this.formatSIPrefix(data.visitors);
        widget.querySelector(this.options.totalPageviewsOverviewItemSelector).innerHTML = this.formatSIPrefix(data.pageviews);
        widget.querySelector(this.options.currentVisitorsOverviewItemSelector).innerHTML = this.formatSIPrefix(data.current_visitors);

        // full minutes
        let minutes = Math.floor(data.visit_duration / 60);
        // remaining seconds
        let seconds = data.visit_duration - minutes * 60;
        widget.querySelector(this.options.visitDurationOverviewItemSelector).innerHTML = (minutes > 0 ? minutes + 'm ' : '') + (seconds > 0 ? seconds + 's' : '');
      }
    }
  }

  return new VisitorsOverTimeWidget();
});
