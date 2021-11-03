define([], function () {

  class PlausibleWidgets {
    constructor() {
      this.options = {
        dashboardItemSelector: '.dashboard-item',
        widgetContentSelector: '.widget-content',
      };
    }

    registerTimeSelector(selectElement) {
      if (selectElement) {
        selectElement.addEventListener('change', function (e) {
          let dashboard = selectElement.closest(".dashboard-grid");
          let widgetsTimeFrameSelects = dashboard.querySelectorAll("[data-widget-type='plausible-timeframe']");
          widgetsTimeFrameSelects.forEach(function (select) {
            // To prevent endless recursion, Bubbles is checked.
            // Only the change triggered by the user is bubbles=true.
            if (select != selectElement && e.bubbles == true) {
              select.value = selectElement.value;
              select.dispatchEvent(new Event('change', {"bubbles": false}));
            }
          });
        });
      }
    }
  }

  return new PlausibleWidgets();
});
