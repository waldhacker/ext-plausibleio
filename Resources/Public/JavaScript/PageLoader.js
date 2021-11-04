define([
  "TYPO3/CMS/Core/Ajax/AjaxRequest",
  "TYPO3/CMS/Core/Event/RegularEvent",
  "lit",
  "TYPO3/CMS/Plausibleio/Contrib/d3-format",
  "TYPO3/CMS/Plausibleio/PlausibleWidgets",
], function (AjaxRequest, RegularEvent, lit, D3Format, PW) {

  class PageLoader {
    constructor() {
      this.options = {
        dashboardItemSelector: '.dashboard-item',
        widgetContentSelector: '.widget-content',
        pageEndpoint: TYPO3.settings.ajaxUrls.plausible_page,
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
        var visitorsSum = 0;

        tabData.data.forEach(function (item) {
          visitorsSum += item.visitors;
        });

        let template = lit.html`
        ${tabData.data.map((item) => {
          let percentage = item.visitors / visitorsSum * 100;
          return lit.html`
          <div class="bar">
            <div>
              <div style="width: ${percentage}%; "></div>
              <span >${item.page}</span>
            </div>
            <span>${D3Format.format(".2~s")(item.visitors)}</span>
          </div>`
        })}
    `;
        tab.innerHTML = "";
        let newChild = document.createElement('div');
        newChild.classList.add('barchart');
        let targetElement = tab.appendChild(newChild);
        
        lit.render(template, targetElement);
      });
    }

    initialize() {
      let that = this;

      new RegularEvent('widgetContentRendered', function (e) {
        e.preventDefault();
        let widget = e.target;

        let pageChartElement = widget.querySelector("[data-widget-type='pageChart']");
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

  return new PageLoader();
});
