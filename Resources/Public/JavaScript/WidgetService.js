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
  'TYPO3/CMS/Backend/Storage/BrowserSession',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format'
], function (lit, BrowserSession, D3Format) {
  'use strict';

  class WidgetService {
    constructor() {
      this.options = {
        dashBoardGridSelector: '.dashboard-grid',
        dashboardItemSelector: '.dashboard-item',
        timeFrameSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]',
        predefinedTimeframeSelectSelector: '[data-widget-plausible-predefined-timeframe]',
        predefinedSiteSelector: '[data-widget-plausible-predefined-site]',
        tabBodyContainerSelector: '.panel-body',
        headingsContainerSelector: '.header',
        filterBarSelector: '.widget-content-filter',
        filterLinkSelector: '[data-widget-plausible-filter]',
        sessionFilterKey: 'plausible-filter',
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

    renderFilterBar(container) {
      let template = lit.html``;
      let filterData = JSON.parse(BrowserSession.get(this.options.sessionFilterKey));
      let extraClass = 'p-0';

      if (Array.isArray(filterData)) {
        template = lit.html`
          ${filterData.map((filter) => {
          return lit.html`
                <span class="filterBadge">${filter.label} <b>${filter.value}</b></span>
              `
        })
        }`;

        if (filterData.count > 0)
          container.classList.add(extraClass);
        else
          container.classList.remove(extraClass);
      }

      lit.render(template, container);
    }

    removeFilterByType(name) {
      let savedFilter = JSON.parse(BrowserSession.get(this.options.sessionFilterKey));
      let newFilterArray = [];

      if (Array.isArray(savedFilter)) {
        savedFilter.forEach(filter => {
            if (filter.name.toLowerCase() != name.toLowerCase())
              newFilterArray.push(filter);
          });
      }

      BrowserSession.set(this.options.sessionFilterKey, JSON.stringify(newFilterArray));
    }

    setFilterActionsForBarChart(barChartContainer) {
      let that = this;
      let filterLinks = barChartContainer.querySelectorAll(this.options.filterLinkSelector);

      filterLinks.forEach(link =>
        link.addEventListener('click', function (e) {
          // add Filter to filter bar and rerender filter bar
          if (link.dataset.widgetPlausibleFilter && link.dataset.widgetPlausibleFilter !== '') {
            let value = link.dataset.widgetPlausibleFilterValue;
            let label = link.dataset.widgetPlausibleFilterLabel ? link.dataset.widgetPlausibleFilterLabel : '';

            if (value) {
              // There may only ever be one filter of each type
              that.removeFilterByType(link.dataset.widgetPlausibleFilter);
              let savedFilter = JSON.parse(BrowserSession.get(that.options.sessionFilterKey));
              if (!Array.isArray(savedFilter))
                savedFilter = [];
              savedFilter.push({name: link.dataset.widgetPlausibleFilter, value: value, label: label});
              BrowserSession.set(that.options.sessionFilterKey, JSON.stringify(savedFilter));
            }

            let dashboardGrid = barChartContainer.closest(that.options.dashBoardGridSelector);
            let widgets = dashboardGrid.querySelectorAll(that.options.dashboardItemSelector);
            widgets.forEach(function (widget) {
              let event = new CustomEvent('plausible:filterchange');
              widget.dispatchEvent(event);
            });
          }
        })
      );
    }

    renderBarChartRowCell(rowData, colData) {
      let cell = lit.html`<span>${rowData[colData.name]}</span>`;

      if (colData.filter && colData.filter.name !== '')
        cell = lit.html`<span><a href="#" data-widget-plausible-filter="${colData.filter.name}" data-widget-plausible-filter-value="${rowData[colData.name]}"  data-widget-plausible-filter-label="${colData.filter.label}">${rowData[colData.name]}</a></span>`;

      return cell;
    }

    renderBarChart(parentElement, data, clear = false) {
      if (typeof(parentElement) === 'undefined' || parentElement === null) {
        console.error('No parent element was specified for the bar chart.')
        return;
      }

      let columns = null;
      if (data.columns !== undefined)
        columns = data.columns;
      if (columns == null || columns.length == 0)
        return;
      let hitColumns = [];
      // skip first item (label of the bar), so we get only the columns on the right side of the bar
      columns.slice(1).forEach(function (value) {
        hitColumns[hitColumns.length] = value;
      });
      let rowsData = data.data;

      const barLabelTemplate = (row) => lit.html`
        <div>
          <div style="width: ${row.percentage}%; "></div>
          ${this.renderBarChartRowCell(row, columns[0])}
        </div>
      `;
      const hitColumnsTemplate = (row) => lit.html`${hitColumns.map((col) =>
        lit.html`
          ${this.renderBarChartRowCell(row, col)}
        `)
      }`;

      let template = lit.html`
        ${rowsData.map((row) => {
        return lit.html`
          <div class="bar">
            ${barLabelTemplate(row)}
            ${hitColumnsTemplate(row)}
          </div>
        `})}
      `;

      if (clear) {
        parentElement.innerHTML = '';
      }

      let newChild = document.createElement('div');
      newChild.classList.add('barchart');
      let targetElement = parentElement.appendChild(newChild);

      lit.render(template, targetElement);

      this.setFilterActionsForBarChart(parentElement);

      let tabBodyContainer = parentElement.closest(this.options.tabBodyContainerSelector);
      if (tabBodyContainer != null) {
        let headingsContainer = tabBodyContainer.querySelector(this.options.headingsContainerSelector);
        if (headingsContainer !== null)
          this.renderBarChartHeadings(headingsContainer, columns);
      }
    }

    renderBarChartHeadings(container, columns, clear = true) {
      const headingsTemplate = lit.html`
        ${columns.map((col, i, columns) => {
            let extraClass = '';

            if (i == 0)
              extraClass = ' firstHeader';
            if (i == columns.length-1)
              extraClass = ' lastHeader';

            return lit.html`<span class="headerText${extraClass}">${col.label}</span>`
          })
        }
      `;

      if (clear) {
        container.innerHTML = '';
      }

      lit.render(headingsTemplate, container);
    }
  }

  return new WidgetService();
});
