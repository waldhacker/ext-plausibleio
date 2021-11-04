define([
  "TYPO3/CMS/Core/Ajax/AjaxRequest",
  "TYPO3/CMS/Core/Event/RegularEvent",
  "datamaps",
  "d3",
  "TYPO3/CMS/Plausibleio/Contrib/d3-format",
  "TYPO3/CMS/Plausibleio/PlausibleWidgets",
], function (AjaxRequest, RegularEvent, Datamap, D3, D3Format, PW) {
  /* The configuration of requirejs is done in
   * CountryDataWidget->preparePageRenderer
   */

  class CountriesLoader {
    constructor() {
      this.options = {
        dashboardItemSelector: '.dashboard-item',
        widgetContentSelector: '.widget-content',
        visitorsCountryEndpoint: TYPO3.settings.ajaxUrls.plausible_countrymap,
      };

      this.initialize();
    }

    requestUpdatedData(evt, map) {
      new AjaxRequest(this.options.visitorsCountryEndpoint)
        .withQueryArguments({
          timeFrame: evt.detail.timeFrame
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          this.setMapData(map, data);
        });
    }

    setMapData(map, data) {
      if (map) {
        /* Highmap code taken from: */
        /* https://github.com/markmarkoh/datamaps/blob/master/README.md#getting-started */

        // We need to colorize every country based on "numberOfWhatever"
        // colors should be uniq for every value.
        // For this purpose we create palette(using min/max series-value)
        var onlyValues = data.map(function (obj) {
          return obj[1];
        });
        var minValue = Math.min.apply(null, onlyValues);
        var maxValue = Math.max.apply(null, onlyValues);

        // create color palette function
        // color can be whatever you wish
        var paletteScale = D3.scale.linear()
          .domain([minValue, maxValue])
          .range(["#EFEFFF", "#02386F"]); // blue color

        // Datamaps expect data in format:
        // { "USA": { "fillColor": "#42a844", numberOfWhatever: 75},
        //   "FRA": { "fillColor": "#8dc386", numberOfWhatever: 43 } }
        var dataset = {};

        // fill dataset in appropriate format
        data.forEach(function (item) {
          // item example value ["USA", 70]
          var iso = item[0];
          var value = item[1];
          value = D3Format.format(".2~s")(value); // 2400 -> 2.4k
          dataset[iso] = {numberOfThings: value, fillColor: paletteScale(value)};
        });

        map.updateChoropleth(null, {reset: true}); // reset all countries to default color
        map.updateChoropleth(dataset);
      }
    }

    initialize() {
      let that = this;

      new RegularEvent('widgetContentRendered', function (e) {
        e.preventDefault();
        let widget = e.target;

        let mapElement = widget.querySelector("[data-widget-type='countryMap']");
        if (mapElement) {
          // render map
          let map = new Datamap({
            element: mapElement,
            projection: 'mercator', // big world map
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

          widget.addEventListener('timeframechange', function (evt) {
            that.requestUpdatedData(evt, map);
          });

          let timeFrameSelect = e.target.querySelector("[data-widget-type='plausible-timeframe']");
          PW.registerTimeSelector(timeFrameSelect);

          PW.dispatchTimeFrameChange(widget, timeFrameSelect.value); // request and render data
        }

      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new CountriesLoader();
});
