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
  'use strict';

  class WidgetService {
    constructor() {
      this.options = {
        dashBoardGridSelector: '.dashboard-grid',
        dashboardItemSelector: '.dashboard-item',
        timeFrameSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]'
      };
    }

    registerTimeSelector(selectElement) {
      let that = this;

      if (typeof(selectElement) === 'undefined' || selectElement === null) {
          return;
      }

      selectElement.addEventListener('change', function (e) {
        let callingSelect = e.target;
        let dashboard = callingSelect.closest(that.options.dashBoardGridSelector);
        let widgetContent = callingSelect.closest(that.options.dashboardItemSelector);
        let widgets = dashboard.querySelectorAll(that.options.dashboardItemSelector);
        let widgetsTimeFrameSelects = dashboard.querySelectorAll(that.options.timeFrameSelector);
        let widgetSiteSelect = widgetContent.querySelector(that.options.siteSelector);

        widgetsTimeFrameSelects.forEach(function (select) {
          if (select !== callingSelect) {
            select.value = callingSelect.value;
          }
        });

        widgets.forEach(function (widget) {
          that.dispatchTimeFrameChange(widget, widgetSiteSelect.value, callingSelect.value);
        });
      });
    }

    registerSiteSelector(selectElement) {
      let that = this;

      if (typeof(selectElement) === 'undefined' || selectElement === null) {
        return;
      }

      selectElement.addEventListener('change', function (e) {
        let callingSelect = e.target;
        let dashboard = callingSelect.closest(that.options.dashBoardGridSelector);
        let widgetContent = callingSelect.closest(that.options.dashboardItemSelector);
        let widgets = dashboard.querySelectorAll(that.options.dashboardItemSelector);
        let widgetsSiteSelects = dashboard.querySelectorAll(that.options.siteSelector);
        let widgetTimeFrameSelect = widgetContent.querySelector(that.options.timeFrameSelector);

        widgetsSiteSelects.forEach(function (select) {
          if (select !== callingSelect) {
            select.value = callingSelect.value;
          }
        });

        widgets.forEach(function (widget) {
          that.dispatchSiteChange(widget, callingSelect.value, widgetTimeFrameSelect.value);
        });
      });
    }

    dispatchTimeFrameChange(widget, siteId, timeFrame) {
      if (typeof(widget) === 'undefined' || widget === null) {
        return;
      }

      let event = new CustomEvent('plausible:timeframechange', {
        detail: {
          siteId: siteId,
          timeFrame: timeFrame
        }
      });
      widget.dispatchEvent(event);
    }

    dispatchSiteChange(widget, siteId, timeFrame) {
      if (typeof(widget) === 'undefined' || widget === null) {
        return;
      }

      let event = new CustomEvent('plausible:sitechange', {
        detail: {
          siteId: siteId,
          timeFrame: timeFrame
        }
      });
      widget.dispatchEvent(event);
    }

    renderBarChart(parentElement, data, clear = false) {
      if (typeof(parentElement) === 'undefined' || parentElement === null) {
        console.error('No parent element was specified for the bar chart.')
        return;
      }

      let visitorsSum = 0;
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
