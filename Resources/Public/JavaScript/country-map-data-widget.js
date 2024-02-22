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

import AjaxRequest from '@typo3/core/ajax/ajax-request.js';
import RegularEvent from '@typo3/core/event/regular-event.js';
import WidgetService from  '@waldhacker/plausibleio/widget-service.js';
import * as D3 from 'd3';
import * as Datamap from 'datamaps';
import { format } from 'd3-format';

class CountryMapDataWidget {
  constructor() {
    this.options = {
      dashboardItemSelector: '[data-widget-key^="plausible.countrymapdata"]',
      widgetContainerSelector: '[data-widget-type="countryMapData"]',
      timeframeSelectSelector: '[data-widget-plausible-timeframe-select]',
      siteSelector: '[data-widget-plausible-sites-select]',
      visitorsCountryEndpoint: TYPO3.settings.ajaxUrls.plausible_countrymapdata
    };

    this.initialize();
  }

  requestUpdatedData(evt, map) {
    new AjaxRequest(this.options.visitorsCountryEndpoint)
      .withQueryArguments({
        timeFrame: evt.detail.timeFrame,
        siteId: evt.detail.siteId
      })
      .get()
      .then(async (response) => {
        const data = await response.resolve();
        this.setMapData(map, data);
      });
  }

  setMapData(map, data) {
    if (map && data && data.length > 0) {
      /* Highmap code taken from: */
      /* https://github.com/markmarkoh/datamaps/blob/master/README.md#getting-started */

      // We need to colorize every country based on 'numberOfWhatever'
      // colors should be uniq for every value.
      // For this purpose we create palette(using min/max series-value)
      let onlyValues = data.map(function (obj) {
        return obj[1];
      });
      let minValue = Math.min.apply(null, onlyValues);
      let maxValue = Math.max.apply(null, onlyValues);

      // create color palette function
      // color can be whatever you wish
      let paletteScale = D3.default.scale.linear()
        .domain([minValue, maxValue])
        .range(['#e0f3f8', '#4575b4']); // blue color

      // Datamaps expect data in format:
      // { 'USA': { 'fillColor': '#42a844', numberOfWhatever: 75},
      //   'FRA': { 'fillColor': '#8dc386', numberOfWhatever: 43 } }
      let dataset = {};

      // fill dataset in appropriate format
      data.forEach(function (item) {
        // item example value ['USA', 70]
        let iso = item[0];
        let value = item[1];
        // 2400 -> 2.4k
        value = format('.2~s')(value);
        dataset[iso] = {numberOfThings: value, fillColor: paletteScale(value)};
      });

      // reset all countries to default color
      map.updateChoropleth(null, {reset: true});
      map.updateChoropleth(dataset);
    } else {
      map.updateChoropleth(null, {reset: true});
      map.updateChoropleth({});
    }
  }

  initialize() {
    let that = this;

    new RegularEvent('widgetContentRendered', function (evt) {
      evt.preventDefault();
      let widget = evt.target;

      let mapElement = widget.querySelector(that.options.widgetContainerSelector);
      if (typeof(mapElement) !== 'undefined' && mapElement !== null) {
        // render map
        let map = new Datamap.default({
          element: mapElement,
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
          }
        });

        widget.addEventListener('plausible:timeframechange', function (evt) {
          that.requestUpdatedData(evt, map);
        });

        widget.addEventListener('plausible:sitechange', function (evt) {
          that.requestUpdatedData(evt, map);
        });

        let timeFrameSelect = widget.querySelector(that.options.timeframeSelectSelector);
        if (typeof(timeFrameSelect) !== 'undefined' && timeFrameSelect !== null) {
          WidgetService.registerTimeSelector(timeFrameSelect);
        }

        let siteSelect = widget.querySelector(that.options.siteSelector);
        if (typeof(siteSelect) !== 'undefined' && siteSelect !== null) {
          WidgetService.registerSiteSelector(siteSelect);
        }

        // request and render data
        let configuration = WidgetService.getSiteAndTimeFrameFromDashboardItem(widget);
        WidgetService.dispatchTimeFrameChange(widget, configuration.site, configuration.timeFrame);
      }

    }).delegateTo(document, this.options.dashboardItemSelector);
  }
}

export default new CountryMapDataWidget();
