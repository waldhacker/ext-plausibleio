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
  'd3',
  'datamaps',
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'TYPO3/CMS/Plausibleio/Contrib/d3-format',
  'TYPO3/CMS/Plausibleio/WidgetService',
  'TYPO3/CMS/Plausibleio/Tabs',
], function (D3, Datamap, AjaxRequest, RegularEvent, D3Format, WidgetService, Tabs) {
  'use strict';

  class CountryMapDataWidget {
    constructor() {
      this.options = {
        dashboardItemSelector: '[data-widget-key^="plausible.countrymapdata"]',
        widgetContainerSelector: '[data-widget-type="countryMapData"]',
        timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
        siteSelector: '[data-widget-plausible-sites-select]',
        tabSelector: '[data-widget-tab-id="${tabId}"]',
        tabBarSelector: '.t3js-tabs',
        visitorsCountryEndpoint: TYPO3.settings.ajaxUrls.plausible_countrymapdata
      };

      this.initialize();
    }

    requestUpdatedData(evt, widget, map) {
      WidgetService.checkDataForRequest(evt);

      new AjaxRequest(this.options.visitorsCountryEndpoint)
        .withQueryArguments({
          dashboard: evt.detail.dashboard,
          timeFrame: evt.detail.timeFrame,
          siteId: evt.detail.siteId,
          filter: evt.detail.filter,
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          data.forEach(function (tabData) {
            if (tabData.tab === 'map') {
              this.setMapData(map, tabData.data);
            }
            if (tabData.tab === 'countries') {
              let tab = widget.querySelector(this.options.tabSelector.replace('${tabId}', 'countries'));
              if (tab != null) {
                WidgetService.renderBarChartToElement(tab, tabData.data, true);
              }
            }
          }, this);
        }).catch(error => {
            let msg = error.response ? error.response.status + ' ' + error.response.statusText : 'unknown';
            console.error('Map controller request failed because of error: ' + msg);
          }
        );
    }

    setMapData(map, data) {
      if (map && data && data.length > 0) {
        /* Highmap code taken from: */
        /* https://github.com/markmarkoh/datamaps/blob/master/README.md#getting-started */

        // We need to colorize every country based on 'numberOfWhatever'
        // colors should be uniq for every value.
        // For this purpose we create palette(using min/max series-value)
        let onlyValues = data.map(function (obj) {
          return obj.visitors;
        });
        let minValue = Math.min.apply(null, onlyValues);
        let maxValue = Math.max.apply(null, onlyValues);

        // create color palette function
        // color can be whatever you wish
        let maxColor = '#4575b4';
        // if only one country has a dataset, then maxColor is used for it
        let minColor = minValue == maxValue ? maxColor : '#e0f3f8';
        let paletteScale = D3.scale.linear()
          .domain([minValue, maxValue])
          .range([minColor, maxColor]);

        // Datamaps expect data in format:
        // { 'USA': { 'fillColor': '#42a844', numberOfWhatever: 75},
        //   'FRA': { 'fillColor': '#8dc386', numberOfWhatever: 43 } }
        let dataset = {};

        // fill dataset in appropriate format
        data.forEach(function (item) {
          // item example value ['USA', 70]
          let iso = item.alpha3;
          let value = item.visitors;
          // 2400 -> 2.4k
          value = D3Format.format('.2~s')(value);
          dataset[iso] = {
            numberOfThings: value,
            fillColor: paletteScale(item.visitors),
            filter: {
              name: 'visit:country',
              value: item.alpha2,
              label: WidgetService.getLL('filter.deviceData.countryIs', 'Country is'),
              labelValue: item.country,
            }
          };
        });

        // reset all countries to default color
        map.updateChoropleth(null, {reset: true});
        map.updateChoropleth(dataset);
      } else {
        map.updateChoropleth(null, {reset: true});
        map.updateChoropleth({});
      }
    }

    resizeMap(widget, map) {
      let mapTab = widget.querySelector('.widget-content-main');
      let mapContainer = null;
      let aspect = 4 / 2.8;

      if (mapTab) {
        mapContainer = widget.querySelector('[data-widget-tab-id="map"]');
        if (mapContainer) {
          let h = mapTab.clientHeight - 80;
          let w = mapTab.clientWidth;

          if (w > h * aspect) {
            w = Math.min(mapTab.clientHeight * aspect, mapTab.clientWidth);
          }
          w = w - 80;

          mapContainer.setAttribute('style', 'height:auto'); // -> reset mapTap to height from parent not from mapContainer
          mapContainer.setAttribute('style', 'height:' + (mapTab.clientHeight - 130) + 'px;' + 'width:' + w + 'px;');
        }

        map.resize();
      }
    }

    initialize() {
      let that = this;

      new RegularEvent('widgetContentRendered', function (evt) {
        evt.preventDefault();
        let widget = evt.target;
        let filterBar = widget.querySelector(WidgetService.options.filterBarSelector);

        let mapElement = widget.querySelector(that.options.widgetContainerSelector);
        if (mapElement != null) {
          // render map
          let map = new Datamap({
            element: mapElement,
            responsive: true,
            // big world map
            projection: 'mercator',
            // countries don't listed in dataset will be painted with this color
            fills: {defaultFill: '#F5F5F5'},
            //data: dataset,
            geographyConfig: {
              borderColor: '#DEDEDE',
              highlightBorderWidth: 1,
              // don't change color on mouse hover
              highlightFillColor: function (geo) {
                return geo['fillColor'] || '#F5F5F5';
              },
              // only change border
              highlightBorderColor: '#B7B7B7',
              // show desired information in tooltip
              popupTemplate: function (geo, data) {
                // don't show tooltip if country don't present in dataset
                if (!data.hasOwnProperty('numberOfThings')) {
                  return;
                }
                // tooltip content
                return ['<div class="hoverinfo">',
                  '<strong>', geo.properties.name, '</strong>',
                  '<br><strong>', data.numberOfThings, '</strong> Visitors',
                  '</div>'].join('');
              }
            },
            done: function (datamap) {
              // Event handler for click on map
              datamap.svg.selectAll('.datamaps-subunit').on('click', function (geography) {
                let data = JSON.parse(this.dataset.info);
                if (data.hasOwnProperty('numberOfThings')) {
                  let dashboardGrid = this.closest(WidgetService.options.dashBoardGridSelector);
                  if (dashboardGrid) {
                    WidgetService.addFilterToFilterBar(
                      dashboardGrid,
                      // this is set in setMapData
                      data.filter.name,
                      data.filter.value,
                      data.filter.label,
                      data.filter.labelValue
                    );
                    that.resizeMap(widget, datamap);
                  }
                }
              });
            },
          });

          widget.addEventListener('plausible:timeframechange', function (evt) {
            that.requestUpdatedData(evt, widget, map);
          });

          widget.addEventListener('plausible:sitechange', function (evt) {
            that.requestUpdatedData(evt, widget, map);
          });

          // Set filters from BE user configuration
          WidgetService.setFilters(evt.detail.filters);
          widget.addEventListener('plausible:filterchange', function (evt) {
            if (filterBar) {
              WidgetService.renderFilterBar(filterBar);
            }
            that.resizeMap(widget, map);
            that.requestUpdatedData(evt, widget, map);
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

          // for responsive size
          that.resizeMap(widget, map);
          window.addEventListener('resize', function () {
            that.resizeMap(widget, map);
          });

          // Without this code, the map is not displayed when switching from the list tab to the map tab.
          let tabBar = widget.querySelector(that.options.tabBarSelector);
          if (tabBar) {
            tabBar.addEventListener('show.bs.tab', function (e) {
              // tab must be visible before redraw -> wait a bit
              setTimeout(function () {
                  that.resizeMap(widget, map)
                },
                300
              );
            });
          }

          Tabs.registerTabsForSessionHandling(widget);
        }

      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new CountryMapDataWidget();
});
