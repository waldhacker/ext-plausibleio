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
define(["require", "exports", "lit", "TYPO3/CMS/Dashboard/Contrib/chartjs", "TYPO3/CMS/Core/Ajax/AjaxRequest", "TYPO3/CMS/Core/Event/RegularEvent"], function (require, exports, lit_1, chartjs_1, AjaxRequest, RegularEvent) {
    "use strict";
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
        requestUpdatedData(evt, chart) {
            new AjaxRequest(this.options.visitorTimeSeriesEndpoint)
                .withQueryArguments({
                timeFrame: evt.target.value
            })
                .get()
                .then(async (response) => {
                const data = await response.resolve();
                chart.data.labels = data.labels;
                chart.data.datasets = data.datasets;
                chart.update();
            });
        }
        initialize() {
            let that = this;
            new RegularEvent('widgetContentRendered', function (evt) {
                evt.preventDefault();
                const config = evt.detail;
                if (undefined === config || undefined === config.graphConfig) {
                    return;
                }
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
                that.renderTimeSelector(visitorsWidgetChart, config);
            }).delegateTo(document, this.options.dashboardItemSelector);
        }
        renderTimeSelector(visitorsWidgetChart, config) {
            const widgetContentArea = visitorsWidgetChart.canvas.closest(this.options.widgetContentSelector);
            const newChild = document.createElement('div');
            newChild.classList.add(this.options.contentFooterClass);
            const targetElement = widgetContentArea.appendChild(newChild);
            const template = lit_1.html `
<div class="form-floating">
  <select class="form-select" id="plausible-timeframe" aria-label="${TYPO3.lang.timeframeselect_aria}" @change="${(evt) => this.requestUpdatedData(evt, visitorsWidgetChart)}">
  ${config.selectorConfig.map((item) => {
                if (item.default) {
                    return lit_1.html `
            <option value="${item.value}" selected="selected">${item.label}</option>`;
                }
                else {
                    return lit_1.html `
            <option value="${item.value}">${item.label}</option>`;
                }
            })}
  </select>
  <label for="plausible-timeframe">${TYPO3.lang.timeframeselect_label}</label>
</div>
    `;
            lit_1.render(template, targetElement);
        }
    }
    return new VisitorLoader();
});
