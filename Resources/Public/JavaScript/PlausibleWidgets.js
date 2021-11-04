define([], function () {

  class PlausibleWidgets {
    constructor() {
      this.options = {
        dashBoardGridSelector: ".dashboard-grid",
        dashboardItemSelector: ".dashboard-item",
        widgetContentSelector: ".widget-content",
        timeFrameSelector: "[data-widget-type='plausible-timeframe']",
      };
    }

    registerTimeSelector(selectElement) {
      let that = this;

      if (selectElement) {
        selectElement.addEventListener('change', function (e) {
          let callingSelect = e.target;
          let dashboard = callingSelect.closest(that.options.dashBoardGridSelector);
          let widgetsTimeFrameSelects = dashboard.querySelectorAll(that.options.timeFrameSelector);
          widgetsTimeFrameSelects.forEach(function (select) {
            if (select != callingSelect) {
              select.value = callingSelect.value;
            }
          });

          let widgets = dashboard.querySelectorAll(that.options.dashboardItemSelector);
          widgets.forEach(function (widget) {
            that.dispatchTimeFrameChange(widget, callingSelect.value);
          });
        });
      }
    }

    dispatchTimeFrameChange(widget, timeFrame) {
      let event = new CustomEvent("timeframechange", {detail: {timeFrame: timeFrame}});
      widget.dispatchEvent(event);
    }
  }

  return new PlausibleWidgets();
});
