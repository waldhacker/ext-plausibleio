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
  'lit',
  'TYPO3/CMS/Dashboard/Contrib/chartjs',
  'TYPO3/CMS/Plausibleio/WidgetService',
], function (AjaxRequest, RegularEvent, lit, chartjs_1, WidgetService) {
  'use strict';
  chartjs_1 = __importDefault(chartjs_1);

  class VisitorsOverTimeWidget {
    constructor() {
      this.options = {
        dashboardItemSelector: '[data-widget-key^="plausible.visitorsovertime"]',
        widgetContainerSelector: '[data-widget-type="visitorsChart"]',
        timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]',
        overviewContainerSelector: '.chartOverviewContainer',
        visitorTimeSeriesEndpoint: TYPO3.settings.ajaxUrls.plausible_visitorsovertime
      };
      this.initialize();
    }

    requestUpdatedData(evt, widget, chart) {
      WidgetService.checkDataForRequest(evt);

      new AjaxRequest(this.options.visitorTimeSeriesEndpoint).withQueryArguments({
        dashboard: evt.detail.dashboard,
        timeFrame: evt.detail.timeFrame,
        siteId: evt.detail.siteId,
        filter: evt.detail.filter,
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
      }).catch(error => {
            let msg = error.response ? error.response.status + ' ' + error.response.statusText : 'unknown';
            console.error('Visitors over time controller request failed because of error: ' + msg);
          }
        );
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
        let filterBar = widget.querySelector(WidgetService.options.filterBarSelector);

        widget.addEventListener('plausible:timeframechange', function (evt) {
          that.requestUpdatedData(evt, widget, visitorsWidgetChart);
        });

        widget.addEventListener('plausible:sitechange', function (evt) {
          that.requestUpdatedData(evt, widget, visitorsWidgetChart);
        });

        // Set filters from BE user configuration
        WidgetService.setFilters(evt.detail.filters);
        widget.addEventListener('plausible:filterchange', function (evt) {
          if (filterBar) {
            WidgetService.renderFilterBar(filterBar);
          }
          that.requestUpdatedData(evt, widget, visitorsWidgetChart);
        });

        let timeFrameSelect = widget.querySelector(that.options.timeframeSelectSelector);
        if (timeFrameSelect != null) {
          WidgetService.registerTimeSelector(timeFrameSelect);
        }

        let siteSelect = widget.querySelector(that.options.siteSelector);
        if (siteSelect != null) {
          WidgetService.registerSiteSelector(siteSelect);
        }

        // request and render data
        let configuration = WidgetService.getSiteAndTimeFrameFromDashboardItem(widget);
        WidgetService.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame, WidgetService.getFilters());

        WidgetService.renderFilterBar(filterBar);
      }).delegateTo(document, this.options.dashboardItemSelector);
    }

    renderOverviewData(widget, data) {
      if (typeof(widget) !== 'undefined' && widget !== null && data) {
        if (data.columns === undefined) {
          return;
        }

        const chartOverviewItemTemplate = (label, value) =>
          lit.html`
            <div class="chartOverviewItem">
              <div class="chartOverviewItemCaption">${label}</div>
              <div class="chartOverviewItemValue">${value !== '' && value != null ? WidgetService.formatSIPrefix(value) : '0'}</div>
            </div>
        `;

        let template = lit.html`
            ${data.columns.map((column) => {
              return lit.html`
              ${chartOverviewItemTemplate(column.label, data.data[column.name])}
            `
        })}
        `;

        let parentElement = widget.querySelector(this.options.overviewContainerSelector);
        if (parentElement == null) {
          return;
        }

        parentElement.innerHTML = '';

        let newChild = document.createElement('div');
        newChild.classList.add('chartOverview');
        let targetElement = parentElement.appendChild(newChild);

        lit.render(template, targetElement, {eventContext: this});
      }
    }
  }

  return new VisitorsOverTimeWidget();
});
