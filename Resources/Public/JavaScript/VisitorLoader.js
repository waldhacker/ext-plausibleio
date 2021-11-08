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
  'require',
  'exports',
  'TYPO3/CMS/Dashboard/Contrib/chartjs',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format',
  'TYPO3/CMS/Plausibleio/PlausibleWidgets',
], function (require, exports, chartjs_1, AjaxRequest, RegularEvent, D3Format, PW) {
    'use strict';
    chartjs_1 = __importDefault(chartjs_1);

    class VisitorLoader {
        constructor() {
            this.options = {
                dashboardItemSelector: '.dashboard-item',
                widgetContentSelector: '.widget-content',
                contentFooterClass: 'widget-content-footer',
                visitorTimeSeriesEndpoint: TYPO3.settings.ajaxUrls.plausible_visitortimeseries
            };
            this.initialize();
        }

        requestUpdatedData(evt, widget, chart) {
            new AjaxRequest(this.options.visitorTimeSeriesEndpoint)
                .withQueryArguments({
                timeFrame: evt.detail.timeFrame
            })
                .get()
                .then(async (response) => {
                const data = await response.resolve();
                if  (chart && data) {
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

              if(!evt.target.querySelector('[data-widget-type="visitorsChart"]'))
                return;

              let visitorsWidgetChart = null;
                chartjs_1.default.helpers.each(chartjs_1.default.instances, function (instance) {
                    const widgetKey = instance.canvas.closest(that.options.dashboardItemSelector).dataset.widgetKey;
                    if (widgetKey === 'plausible.visitorsovertime') {
                        visitorsWidgetChart = instance;
                    }
                });

                if (!visitorsWidgetChart) {
                    return;
                }

                let widget = visitorsWidgetChart.canvas.closest(that.options.dashboardItemSelector);

                widget.addEventListener('timeframechange', function (e) {
                   that.requestUpdatedData(e, widget, visitorsWidgetChart);
                });

                let timeFrameSelect = widget.querySelector('[data-widget-type="plausible-timeframe"]');
                PW.registerTimeSelector(timeFrameSelect);

                // request and render data
                PW.dispatchTimeFrameChange(widget, timeFrameSelect.value);
            }).delegateTo(document, this.options.dashboardItemSelector);
        }

        formatSIPrefix(n) {
          n = D3Format.format('.2~s')(n); // 2400 -> 2.4k
          return n;
        }

        renderOverviewData(widget, data) {
          if (widget && data) {
            widget.querySelector('[data-widget-type="uniqueVisitors"]').innerHTML = this.formatSIPrefix(data.visitors);
            widget.querySelector('[data-widget-type="totalPageviews"]').innerHTML = this.formatSIPrefix(data.pageviews);
            widget.querySelector('[data-widget-type="currentVisitors"]').innerHTML = this.formatSIPrefix(data.current_visitors);

            // full minutes
            var minutes = Math.floor(data.visit_duration / 60);
            // remaining seconds
            var seconds = data.visit_duration - minutes * 60;
            widget.querySelector('[data-widget-type="visitDuration"]').innerHTML = (minutes > 0 ? minutes + 'm ' : '') + (seconds > 0 ? seconds + 's' : '');
          }
        }
    }

    return new VisitorLoader();
});
