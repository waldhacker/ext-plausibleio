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

import {html, TemplateResult, render} from 'lit';
import Chart from 'TYPO3/CMS/Dashboard/Contrib/chartjs';
declare type Chart = typeof import('TYPO3/CMS/Dashboard/Contrib/chartjs');
import AjaxRequest = require('TYPO3/CMS/Core/Ajax/AjaxRequest');
import {AjaxResponse} from 'TYPO3/CMS/Core/Ajax/AjaxResponse';
import RegularEvent = require('TYPO3/CMS/Core/Event/RegularEvent');

interface VisitorLoaderOptions {
  dashboardItemSelector: string
  widgetContentSelector: string
  contentFooterClass: string
  visitorTimeSeriesEndpoint: string
}

class VisitorLoader {
  private options: VisitorLoaderOptions = {
    dashboardItemSelector: '.dashboard-item',
    widgetContentSelector: '.widget-content',
    contentFooterClass: 'widget-content-footer',
    visitorTimeSeriesEndpoint: TYPO3.settings.ajaxUrls.plausible_visitortimeseries
  };

  public constructor() {
    this.initialize()
  }

  public requestUpdatedData(evt: Event, chart: Chart): void {
    new AjaxRequest(this.options.visitorTimeSeriesEndpoint)
      .withQueryArguments({
        timeFrame: (evt.target as HTMLSelectElement).value
      })
      .get()
      .then(async (response: AjaxResponse): Promise<any> => {
        const data = await response.resolve()
        chart.data.labels = data.labels;
        chart.data.datasets = data.datasets;
        chart.update();
      });
  }

  private initialize(): void {
    let that: VisitorLoader = this;
    new RegularEvent('widgetContentRendered', function (evt: CustomEvent): void {
      evt.preventDefault();

      const config: any = evt.detail;

      if (undefined === config || undefined === config.graphConfig) {
        return;
      }

      let visitorsWidgetChart: null|Chart = null;
      Chart.helpers.each(Chart.instances, function (instance: Chart) {
        const widgetKey: string = instance.canvas.closest(that.options.dashboardItemSelector).dataset.widgetKey;

        if (widgetKey === 'plausible.visitorsovertime') {
          visitorsWidgetChart = instance;
        }
      });

      if (!visitorsWidgetChart) {
        return;
      }

      that.renderTimeSelector(visitorsWidgetChart, config);
    }).delegateTo(document, this.options.dashboardItemSelector)
  }

  private renderTimeSelector(visitorsWidgetChart: Chart, config: any): void {
    const widgetContentArea: HTMLElement = visitorsWidgetChart.canvas.closest(this.options.widgetContentSelector)
    const newChild: HTMLDivElement = document.createElement('div')
    newChild.classList.add(this.options.contentFooterClass);
    const targetElement = widgetContentArea.appendChild(newChild);

    const template: TemplateResult = html`
<div class="form-floating">
  <select class="form-select" id="plausible-timeframe" aria-label="${TYPO3.lang.timeframeselect_aria}" @change="${(evt: Event) => this.requestUpdatedData(evt, visitorsWidgetChart)}">
  ${config.selectorConfig.map((item: any) => {
    if (item.default) {
      return html`
            <option value="${item.value}" selected="selected">${item.label}</option>`
    } else {
      return html`
            <option value="${item.value}">${item.label}</option>`
    }
  })}
  </select>
  <label for="plausible-timeframe">${TYPO3.lang.timeframeselect_label}</label>
</div>
    `;

    render(template, targetElement);
  }
}

export = new VisitorLoader();
