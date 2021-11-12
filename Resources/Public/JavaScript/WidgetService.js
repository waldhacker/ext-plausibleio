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
  'lit',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format'
], function (lit, D3Format) {

  class WidgetService {
    constructor() {
      this.options = {
        dashBoardGridSelector: '.dashboard-grid',
        dashboardItemSelector: '.dashboard-item',
        widgetContentSelector: '.widget-content',
        timeFrameSelector: '[data-widget-type="plausible-timeframe"]',
      };
    }

    registerTimeSelector(selectElement) {
      let that = this;

      if (selectElement) {
        selectElement.addEventListener('change', function (e) {
          let callingSelect = e.target;
          let dashboard = callingSelect.closest(that.options.dashBoardGridSelector);
          let widgetsTimeFrameSelects = dashboard.querySelectorAll(that.options.timeFrameSelector);
          widgetsTimeFrameSelects.forEach(function (select) {
            if (select != callingSelect) {
              select.value = callingSelect.value;
            }
          });

          let widgets = dashboard.querySelectorAll(that.options.dashboardItemSelector);
          widgets.forEach(function (widget) {
            that.dispatchTimeFrameChange(widget, callingSelect.value);
          });
        });
      }
    }

    dispatchTimeFrameChange(widget, timeFrame) {
      let event = new CustomEvent('timeframechange', {detail: {timeFrame: timeFrame}});
      if (widget)
        widget.dispatchEvent(event);
    }

    renderBarChart(parentElement, data, clear = false) {
      var visitorsSum = 0;

      if (!parentElement) {
        console.error('No parent element was specified for the bar chart.')
        return;
      }

      data.forEach(function (item) {
        visitorsSum += item.visitors;
      });

      let template = lit.html`
        ${data.map((item) => {
        let percentage = item.visitors / visitorsSum * 100;
        return lit.html`
          <div class="bar">
            <div>
              <div style="width: ${percentage}%; "></div>
              <span >${item.label}</span>
            </div>
            <span>${D3Format.format('.2~s')(item.visitors)}</span>
          </div>`
      })}
    `;

      if (clear) {
        parentElement.innerHTML = '';
      }

      let newChild = document.createElement('div');
      newChild.classList.add('barchart');
      let targetElement = parentElement.appendChild(newChild);

      lit.render(template, targetElement);
    }
  }

  return new WidgetService();
});
