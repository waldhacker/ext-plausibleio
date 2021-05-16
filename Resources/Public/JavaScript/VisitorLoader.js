define([
  'TYPO3/CMS/Core/Ajax/AjaxRequest',
  'TYPO3/CMS/Dashboard/Contrib/chartjs',
  'TYPO3/CMS/Core/Event/RegularEvent',
  'lit-html'
], function (AjaxRequest, Chartjs, RegularEvent, {html, render}) {
  let VisitorLoader = {
    selector: ".dashboard-item",
    contentSelector: ".widget-content"
  };

  VisitorLoader.requestUpdatedData = (evt, chart) => {
      new AjaxRequest(TYPO3.settings.ajaxUrls.plausible_visitortimeseries)
        .withQueryArguments({timeFrame: evt.target.value})
        .get()
        .then(async function (response) {
          const resolved = await response.resolve()
          chart.data.labels = resolved.labels;
          chart.data.datasets = resolved.datasets;
          chart.update();
        });
    };

  VisitorLoader.renderTimeSelector = (visitorsWidgetChart, config) => {
    let widgetContentArea = visitorsWidget.querySelector(VisitorLoader.contentSelector);
    let newChild = document.createElement('div');
    newChild.classList.add('widget-content-footer');
    const target = widgetContentArea.appendChild(newChild, widgetContentArea);
    const template = html`
      <div class="form-floating">
        <select class="form-select" id="plausible-timeframe" aria-label="${TYPO3.lang.timeframeselect_aria}" @change="${(evt) => VisitorLoader.requestUpdatedData(evt, visitorsWidgetChart)}">
          ${config.selectorConfig.map((item) => {
            if (item.default) {
              return html`
                    <option value="${item.value}" selected="selected">${item.label}</option>`
            } else {
              return html`
                    <option value="${item.value}">${item.label}</option>`
            }
          })}
        </select>
        <label for="plausible-timeframe">${TYPO3.lang.timeframeselect_label}</label>
      </div>
    `;
    render(template, target);
  }

  VisitorLoader.init = function () {
    new RegularEvent('widgetContentRendered', function (e) {
      e.preventDefault();
      const config = e.detail;

      if (undefined === config || undefined === config.graphConfig) {
        return;
      }
      let visitorsWidgetChart;
      Chart.helpers.each(Chart.instances, function (instance) {
        const widgetKey = instance.canvas.closest(VisitorLoader.selector).dataset.widgetKey;
        if (widgetKey === 'plausible.visitorsovertime') {
          visitorsWidgetChart = instance;
          visitorsWidget = instance.canvas.closest(VisitorLoader.selector)
        }
      });
      if (!visitorsWidgetChart) {
        return;
      }
      VisitorLoader.renderTimeSelector(visitorsWidgetChart, config);

    }).delegateTo(document, VisitorLoader.selector)
  };

  VisitorLoader.init();
  return VisitorLoader;
});
