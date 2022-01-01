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
  'module',
  'lit',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Backend/Storage/BrowserSession',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format'
], function (module, lit, AjaxRequest, BrowserSession, D3Format) {
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
          that.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame, that.getFilters());
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
          that.dispatchSiteChange(widget, configuration.site, configuration.timeFrame, that.getFilters());
        });
      });
    }

    /**
     *
     * @param Element widget
     * @param string siteId
     * @param string timeFrame
     * @param array[of Filter] filter
     */
    dispatchTimeFrameChange(widget, siteId, timeFrame, filter) {
      if (typeof(widget) === 'undefined' || widget === null) {
        return;
      }

      let event = new CustomEvent('plausible:timeframechange', {
        detail: {
          siteId: siteId,
          timeFrame: timeFrame,
          filter: filter,
        }
      });
      widget.dispatchEvent(event);
    }

    /**
     *
     * @param Element widget receiver of the event
     * @param string siteId
     * @param string timeFrame
     * @param array[of Filter] filter
     */
    dispatchSiteChange(widget, siteId, timeFrame, filter) {
      if (typeof(widget) === 'undefined' || widget === null) {
        return;
      }

      let event = new CustomEvent('plausible:sitechange', {
        detail: {
          siteId: siteId,
          timeFrame: timeFrame,
          filter: filter,
        }
      });
      widget.dispatchEvent(event);
    }

    /**
     *
     * @param Element widget. receiver of the event
     * @param string siteId
     * @param string timeFrame
     * @param array[of Filter] filter
     */
    dispatchFilterChanged(widget, siteId, timeFrame, filter) {
      if (typeof (widget) === 'undefined' || widget === null) {
        return;
      }

      let event = new CustomEvent('plausible:filterchange', {
        detail: {
          siteId: siteId,
          timeFrame: timeFrame, // filter müssen für jedes dashboard einzeln gespeichert werden
          filter: filter,
        }
      });
      widget.dispatchEvent(event);
    }

    /**
     *
     * @returns array[of Filter]
     */
    getFilters(dashBoardId = 'default') {
      let filters = JSON.parse(BrowserSession.get(this.options.sessionFilterKey + dashBoardId));

      if (!Array.isArray(filters)) {
        filters = [];
      }

      return filters;
    }

    /**
     * Note: The filters are also still saved in the BE user configuration (server side). This happens
     * during the Ajax request for data retrieval
     *
     * @param array[of Filter] filterArray
     */
    setFilters(filterArray, dashBoardId = 'default') {
      if (Array.isArray(filterArray)) {
        BrowserSession.set(this.options.sessionFilterKey + dashBoardId, JSON.stringify(filterArray));
      }
    }

    /**
     * Note: The languages must be transmitted via the require.js configuration
     *         $this->pageRenderer->addRequireJsConfiguration([
     *            'config' => [
     *               'TYPO3/CMS/Plausibleio/WidgetService' => [
     *               'lang' => [
     *                  'barChart.labels.os' => $this->getLanguageService()->getLL('barChart.labels.os'),
     *               ],
     *            ],
     *         ],
     *
     * @param id
     * @param defaultValue
     * @returns string
     */
    getLL(id, defaultValue) {
      if (module.config().hasOwnProperty('lang')) {
        if (module.config().lang.hasOwnProperty(id)) {
          return module.config().lang[id];
        }
      }

      return defaultValue;
    }

    /**
     *
     * @param string label
     */
    labelReplacePlaceholder(label) {
      let browserFilter = this.getFilterByType('visit:browser');
      let osFilter = this.getFilterByType('visit:os');
      let existingLabels = {
        browser: browserFilter ? browserFilter.value : this.getLL('barChart.labels.browser', 'Browser'),
        os: osFilter ? osFilter.value : this.getLL('barChart.labels.os', 'Operating system'),
      }

      Object.entries(existingLabels).forEach(([key, value]) => {
        label = label.replace('${' + key + '}', value);
      });

      return label;
    }

    renderFilterBar(container) {
      let template = lit.html``;
      let filterData = this.getFilters();
      let noFiltersExtraClass = 'p-0';

      // render filter badges
      if (Array.isArray(filterData)) {
        template = lit.html`
          ${filterData.map((filter) => {
            let filterLabel = this.labelReplacePlaceholder(filter.label);
            return lit.html`
                    <span class="filterBadge" data-widget-plausible-filter="${filter.name}">
                      <span class="filterBadgeText">${filterLabel} <b>${filter.labelValue}</b></span>
                      <span class="icon icon-size-small icon-state-default" @click=${(event) => this.filterBadgeRemoveButtonOnClick(event)}>
                        <span class="icon-markup">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><g class="icon-color"><path d="M11.9 5.5L9.4 8l2.5 2.5c.2.2.2.5 0 .7l-.7.7c-.2.2-.5.2-.7 0L8 9.4l-2.5 2.5c-.2.2-.5.2-.7 0l-.7-.7c-.2-.2-.2-.5 0-.7L6.6 8 4.1 5.5c-.2-.2-.2-.5 0-.7l.7-.7c.2-.2.5-.2.7 0L8 6.6l2.5-2.5c.2-.2.5-.2.7 0l.7.7c.2.2.2.5 0 .7z"/></g></svg>
                        </span>
                      </span>
                    </span>
                  `
        })
        }`;

        if (filterData.length == 0)
          container.classList.add(noFiltersExtraClass);
        else
          container.classList.remove(noFiltersExtraClass);
      }

      container.innerHTML = '';

      let newChild = document.createElement('div');
      //newChild.classList.add('barchart');
      let targetElement = container.appendChild(newChild);

      lit.render(template, targetElement, {eventContext: this});
    }

    getFilterByType(name) {
      let savedFilter = this.getFilters();
      let result = null

      savedFilter.every(filter => {
        if (filter.name.toLowerCase() == name.toLowerCase()) {
          result = filter;
          return false;
        }
        return true;
      });

      return result;
    }

    removeFilterByType(name) {
      let savedFilter = this.getFilters();
      let newFilterArray = [];

      savedFilter.forEach(filter => {
          if (filter.name.toLowerCase() != name.toLowerCase())
            newFilterArray.push(filter);
        });

      this.setFilters(newFilterArray);
    }

    filterBadgeRemoveButtonOnClick(e) {
      let badge = e.target.closest('.filterBadge');

      if (badge) {
        let type = badge.dataset.widgetPlausibleFilter ? badge.dataset.widgetPlausibleFilter : null;
        if (type) {
          this.removeFilterByType(type);

          let dashboardGrid = badge.closest(this.options.dashBoardGridSelector);
          if (dashboardGrid) {
            let widgets = dashboardGrid.querySelectorAll(this.options.dashboardItemSelector);
            widgets.forEach(function (widget) {
              let configuration = this.getSiteAndTimeFrameFromDashboardItem(widget);
              this.dispatchFilterChanged(widget, configuration.site, configuration.timeFrame, this.getFilters());
            }, this);
          }
        }
      }
    }

    /**
     * Adds a filter to the dashboard and a filter badge to the filter bar.
     *                             ______________________________________
     *                           /                                  \ /  \
     * Structure of the badge:  |   filterLabel filterLabelvalue    /\   |
     *                          \_________________________________/___\_/
     * @param dashboardGrid
     * @param filterName The name of the filter (e.g. visit:country)
     * @param filterValue Value according to which filtering takes place. By default, this is the second half of
     *                    the label of the badge
     * @param filterLabel
     * @param filterLabelValue If filterLabelValue is not specified, its value is that of filterValue. In case the
     *                         second half of the label of Badge should not be filterValue, you can specify a different
     *                         value with this parameter.
     */
    addFilterToFilterBar(dashboardGrid, filterName, filterValue, filterLabel, filterLabelValue='') {
      if (filterValue !== '') {
        filterLabelValue = filterLabelValue !== '' ? filterLabelValue : filterValue;
        // There may only ever be one filter of each type
        this.removeFilterByType(filterName);
        let savedFilters = this.getFilters();
        if (!Array.isArray(savedFilters)) {
          savedFilters = [];
        }
        savedFilters.push({name: filterName, value: filterValue, label: filterLabel, labelValue: filterLabelValue});
        this.setFilters(savedFilters);

        if (dashboardGrid) {
          let widgets = dashboardGrid.querySelectorAll(this.options.dashboardItemSelector);
          widgets.forEach(function (widget) {
            let configuration = this.getSiteAndTimeFrameFromDashboardItem(widget);
            this.dispatchFilterChanged(widget, configuration.site, configuration.timeFrame, this.getFilters());
          }, this);
        }
      }
    }

    chartBarOnClick(e) {
      let link = e.target;

      // add Filter to filter bar and rerender filter bar
      if (link.dataset.widgetPlausibleFilter && link.dataset.widgetPlausibleFilter !== '') {
        let value = link.dataset.widgetPlausibleFilterValue;
        let labelValue = link.dataset.widgetPlausibleFilterLabelValue ? link.dataset.widgetPlausibleFilterLabelValue : value;
        let label = link.dataset.widgetPlausibleFilterLabel ? link.dataset.widgetPlausibleFilterLabel : '';
        let dashboardGrid = link.closest(this.options.dashBoardGridSelector);

        this.addFilterToFilterBar(dashboardGrid, link.dataset.widgetPlausibleFilter, value, label, labelValue);
      }
    }

    renderBarChartRowCell(rowData, colData) {
      let dataValue = rowData[colData.name];
      let filterValue = colData.filter && colData.filter.value ? rowData[colData.filter.value] : dataValue;
      let valueUnknown = this.getLL('barChart.labels.unknown', 'Unknown');
      let cell = '';

      if (dataValue != undefined && dataValue !== '')
        cell = lit.html`<span>${dataValue}</span>`;
      else
        cell = lit.html`<span>${valueUnknown}</span>`;

      if (colData.filter && colData.filter.name !== '' && dataValue !== '')
        cell = lit.html`<span><a href="#" @click=${(event) => this.chartBarOnClick(event)} data-widget-plausible-filter="${colData.filter.name}" data-widget-plausible-filter-value="${filterValue}"  data-widget-plausible-filter-label="${colData.filter.label}" data-widget-plausible-filter-label-value="${dataValue}">${dataValue}</a></span>`;

      return cell;
    }

    renderBarChart(parentElement, data, clear = false) {
      if (typeof(parentElement) === 'undefined' || parentElement === null) {
        console.error('No parent element was specified for the bar chart.')
        return;
      }

      let columns = null;
      if (data.columns !== undefined) {
        columns = data.columns;
      }
      if (columns == null || columns.length == 0) {
        return;
      }
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

      lit.render(template, targetElement, {eventContext: this});

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
            let labelText = this.labelReplacePlaceholder(col.label);

            if (i == 0) {
              extraClass = ' firstHeader';
            }
            if (i == columns.length-1) {
              extraClass = ' lastHeader';
            }

            return lit.html`<span class="headerText${extraClass}">${labelText}</span>`
          })
        }
      `;

      if (clear) {
        container.innerHTML = '';
      }

      let newChild = document.createElement('div');
      let targetElement = container.appendChild(newChild);

      lit.render(headingsTemplate, targetElement);
    }
  }

  return new WidgetService();
});
