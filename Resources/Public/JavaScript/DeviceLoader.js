define([
  "TYPO3/CMS/Core/Ajax/AjaxRequest",
  "TYPO3/CMS/Core/Event/RegularEvent",
  "lit",
  "TYPO3/CMS/Plausibleio/Contrib/d3-format",
  "TYPO3/CMS/Plausibleio/PlausibleWidgets",
], function (AjaxRequest, RegularEvent, lit, D3Format, PW) {

  class DeviceLoader {
    constructor() {
      this.options = {
        dashboardItemSelector: '.dashboard-item',
        widgetContentSelector: '.widget-content',
        pageEndpoint: TYPO3.settings.ajaxUrls.plausible_device,
      };

      this.initialize();
    }

    requestUpdatedData(evt, chartDiv) {
      new AjaxRequest(this.options.pageEndpoint)
        .withQueryArguments({
          timeFrame: evt.detail.timeFrame
        })
        .get()
        .then(async (response) => {
          const data = await response.resolve();
          this.renderChart(chartDiv, data);
        });
    }

    renderChart(chartDiv, data) {
      data.forEach(function (tabData) {
        let tab = chartDiv.querySelector("[data-widget-type='" + tabData.tab + "']");
        if (tab)
          PW.renderBarChart(tab, tabData.data, true);
      });
    }

    initialize() {
      let that = this;

      new RegularEvent('widgetContentRendered', function (e) {
        e.preventDefault();
        let widget = e.target;

        let pageChartElement = widget.querySelector("[data-widget-type='deviceChart']");
        if (pageChartElement) {
          widget.addEventListener('timeframechange', function (evt) {
            that.requestUpdatedData(evt, pageChartElement);
          });

          let timeFrameSelect = e.target.querySelector("[data-widget-type='plausible-timeframe']");
          PW.registerTimeSelector(timeFrameSelect);

          PW.dispatchTimeFrameChange(widget, timeFrameSelect.value); // request and render data
        }

      }).delegateTo(document, this.options.dashboardItemSelector);
    }
  }

  return new DeviceLoader();
});
