define([
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'datamaps',
  'd3',
  "TYPO3/CMS/Plausibleio/Contrib/d3-format",
], function (AjaxRequest, RegularEvent, Datamap, D3, D3Format) {
/* The configuration of requirejs is done in  */
/* CountryDataWidget->preparePageRenderer     */

  let CountriesLoader = {
    selector: ".dashboard-item",
    contentSelector: ".widget-content"
  };

  // widgetRefresh

  CountriesLoader.init = function () {
    new RegularEvent('widgetContentRendered', function (e) {
      e.preventDefault();
      const config = e.detail;

//      if (config === undefined || config.widgetId == "" || config.widgetId == undefined) {
      if (config === undefined) {
          return;
      }

      let mapElement = e.target.querySelector("[data-widget-type='countryMap']"); //document.getElementById(config.widgetId);
      if (mapElement) {
        /* Highmap code taken from: */
        /* https://github.com/markmarkoh/datamaps/blob/master/README.md#getting-started */

        // We need to colorize every country based on "numberOfWhatever"
        // colors should be uniq for every value.
        // For this purpose we create palette(using min/max series-value)
        var onlyValues = config.data.map(function (obj) {
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
        config.data.forEach(function (item) {
          // item example value ["USA", 70]
          var iso = item[0];
          var value = item[1];
          value = D3Format.format(".2~s")(value); // 2400 -> 2.4k
          dataset[iso] = {numberOfThings: value, fillColor: paletteScale(value)};
        });

        // render map
        new Datamap({
          element: mapElement,
          projection: 'mercator', // big world map
          // countries don't listed in dataset will be painted with this color
          fills: {defaultFill: '#F5F5F5'},
          data: dataset,
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
              if (!data) {
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
      }

    }).delegateTo(document, CountriesLoader.selector);
  };

  CountriesLoader.init();
  return CountriesLoader;
});
