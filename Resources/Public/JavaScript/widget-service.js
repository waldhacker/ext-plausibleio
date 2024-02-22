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

import { html, render } from 'lit';
import { format } from 'd3-format';

class WidgetService {
  constructor() {
    this.options = {
      dashBoardGridSelector: '.dashboard-grid',
      dashboardItemSelector: '.dashboard-item',
      timeFrameSelector: '[data-widget-plausible-timeframe-select]',
      siteSelector: '[data-widget-plausible-sites-select]',
      predefinedTimeframeSelectSelector: '[data-widget-plausible-predefined-timeframe]',
      predefinedSiteSelector: '[data-widget-plausible-predefined-site]'
    };
  }

  getSiteAndTimeFrameFromDashboardItem(dashboardItem) {
      let configuration = {
        site: '',
        timeFrame: '',
      };

      if (typeof(dashboardItem) === 'undefined' || dashboardItem === null) {
        return configuration;
      }

      let timeFrameSelect = dashboardItem.querySelector(this.options.timeFrameSelector);
      let siteSelect = dashboardItem.querySelector(this.options.siteSelector);
      let predefinedTimeframeElement = dashboardItem.querySelector(this.options.predefinedTimeframeSelectSelector);
      let predefinedSiteElement = dashboardItem.querySelector(this.options.predefinedSiteSelector);
      if (typeof(predefinedSiteElement) !== 'undefined' && predefinedSiteElement !== null) {
        configuration.site = predefinedSiteElement.dataset.widgetPlausiblePredefinedSite;
      } else if (typeof(siteSelect) !== 'undefined' && siteSelect !== null) {
        configuration.site = siteSelect.value;
      }

      if (typeof(predefinedTimeframeElement) !== 'undefined' && predefinedTimeframeElement !== null) {
        configuration.timeFrame = predefinedTimeframeElement.dataset.widgetPlausiblePredefinedTimeframe;
      } else if (typeof(timeFrameSelect) !== 'undefined' && timeFrameSelect !== null) {
        configuration.timeFrame = timeFrameSelect.value;
      }

      return configuration;
  }

  registerTimeSelector(selectElement) {
    let that = this;

    if (typeof(selectElement) === 'undefined' || selectElement === null) {
        return;
    }

    selectElement.addEventListener('change', function (e) {
      let callingSelect = e.target;
      let dashboardGrid = callingSelect.closest(that.options.dashBoardGridSelector);
      let dashboardItem = callingSelect.closest(that.options.dashboardItemSelector);
      let widgets = dashboardGrid.querySelectorAll(that.options.dashboardItemSelector);
      let widgetsTimeFrameSelects = dashboardGrid.querySelectorAll(that.options.timeFrameSelector);

      widgetsTimeFrameSelects.forEach(function (select) {
        if (select !== callingSelect) {
          select.value = callingSelect.value;
        }
      });

      widgets.forEach(function (widget) {
        let configuration = that.getSiteAndTimeFrameFromDashboardItem(widget);
        that.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame);
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
      let dashboardGrid = callingSelect.closest(that.options.dashBoardGridSelector);
      let dashboardItem = callingSelect.closest(that.options.dashboardItemSelector);
      let widgets = dashboardGrid.querySelectorAll(that.options.dashboardItemSelector);
      let widgetsSiteSelects = dashboardGrid.querySelectorAll(that.options.siteSelector);

      widgetsSiteSelects.forEach(function (select) {
        if (select !== callingSelect) {
          select.value = callingSelect.value;
        }
      });

      widgets.forEach(function (widget) {
        let configuration = that.getSiteAndTimeFrameFromDashboardItem(widget);
        that.dispatchSiteChange(widget, configuration.site, configuration.timeFrame);
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

    let template = html`
      ${data.map((item) => {
      let percentage = item.visitors / visitorsSum * 100;
      return html`
        <div class="bar">
          <div>
            <div style="width: ${percentage}%; "></div>
            <span >${item.label}</span>
          </div>
          <span>${format('.2~s')(item.visitors)}</span>
        </div>`
    })}
  `;

    if (clear) {
      parentElement.innerHTML = '';
    }

    let newChild = document.createElement('div');
    newChild.classList.add('barchart');
    let targetElement = parentElement.appendChild(newChild);

    render(template, targetElement);
  }
}

export default new WidgetService();
