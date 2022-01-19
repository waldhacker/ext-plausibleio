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
  'TYPO3/CMS/Backend/Storage/BrowserSession',
  'jquery',
], function (BrowserSession, jquery_1) {
  'use strict';
  jquery_1 = __importDefault(jquery_1);

  class Tabs {

    constructor() {
      this.options = {
        tabBarSelector: '.t3js-tabs',
        tabBarTabItemSelector: '.t3js-tabmenu-item A',
      };
    }

    /**
     * In order to determine the last tab that was selected, you need Ids that
     * are constant across the sessions. To get constant Ids across sessions,
     * the Id must be taken from the widget, because this is constant.
     *
     * @param tabBar element
     * @param widget element
     */
    setTabIdsFromWidgetId(tabBar, widget) {
      if (typeof (tabBar) === 'undefined' || tabBar === null) {
        console.error('No valid tabBar was specified.');
        return;
      }
      if (typeof (widget) === 'undefined' || widget === null) {
        console.error('No valid widget was specified.');
        return;
      }

      let widgetId = 0;
      if (!widget.dataset.hasOwnProperty('widgetHash') || widget.dataset.widgetHash == '') {
        console.error('No valid widget id was found.');
        return;
      } else {
        widgetId = widget.dataset.widgetHash;
      }

      // set new constant id for tabBar
      tabBar.id = tabBar.id + '-' + widgetId;

      let tabs = tabBar.querySelectorAll(this.options.tabBarTabItemSelector);
      let newTabId = 1;
      tabs.forEach(function (tab) {
        // set new constant link from widget-id
        let oldHref = tab.getAttribute('href');
        let newHref = tabBar.id + '-tab-' + newTabId;
        tab.setAttribute('href', '#' + newHref);
        // set id of corresponding content div
        let contentDiv = widget.querySelector(oldHref);
        if (contentDiv != null) {
          contentDiv.id = newHref;
        }

        newTabId++;
      });
    }

    /**
     * Register tabs of a single tabBar for session handling and set last from user selected tab
     *
     * @param widget element
     */
    registerTabBarForSessionHandling(tabBar, widget) {
      if (typeof (tabBar) === 'undefined' || tabBar === null) {
        console.error('No valid tabBar was specified.');
        return;
      }

      this.storeLastActiveTab = tabBar.dataset.storeLastTab === '1';

      if (this.storeLastActiveTab) {
        this.setTabIdsFromWidgetId(tabBar, widget);

        const $tabContainer = jquery_1.default(tabBar);
        const currentActiveTab = this.receiveActiveTab(tabBar.id);
        if (currentActiveTab) {
          $tabContainer.find('a[href="' + currentActiveTab + '"]').tab('show');
        }

        tabBar.addEventListener('show.bs.tab', (function (e) {
          const id = e.currentTarget.id;
          const target = e.target.hash;
          this.storeActiveTab(id, target);
        }).bind(this));
      }
    }

    /**
     * Register tabs of a widget for session handling and set last from user selected tab
     *
     * @param widget element
     */
    registerTabsForSessionHandling(widget) {
       if (typeof (widget) === 'undefined' || widget === null) {
        console.error('No valid widget was specified.');
        return;
      }

      let tabBars = widget.querySelectorAll(this.options.tabBarSelector);
      tabBars.forEach(function (tabBar) {
        this.registerTabBarForSessionHandling(tabBar, widget);
      }, this);
    }

    /**
     * Receive active tab from storage
     *
     * @param {string} id
     * @returns {string}
     */
    receiveActiveTab(id) {
      return BrowserSession.get(id) || '';
    }

    /**
     * Set active tab to storage
     *
     * @param {string} id
     * @param {string} target
     */
    storeActiveTab(id, target) {
      if (id !== '' && target !== '') {
        BrowserSession.set(id, target);
      }
    }

  }

  return new Tabs();
});
