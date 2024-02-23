# Changelog


## 3.0.0 (2024-02-22)

### Tasks

* TYPO3 v13 compat. [waldhacker1]

### Other

* Merge tag '2.2.1' into develop. [waldhacker1]

  2.2.1


## 2.2.1 (2024-02-22)

### Tasks

* Version 2.2.1. [waldhacker1]

### Bugfixes

* Chart v4 compat. [waldhacker1]

### Other

* Merge branch 'release/2.2.1' [waldhacker1]

* Merge tag '2.2.0' into develop. [waldhacker1]

  2.2.0


## 2.2.0 (2024-02-22)

### Tasks

* Version 2.2.0. [waldhacker1]

* TYPO3 v12 compat. [waldhacker1]

### Other

* Merge branch 'release/2.2.0' [waldhacker1]


## 2.1.0 (2022-12-01)

### Tasks

* Generate changelog. [waldhacker1]

* Version bump. [waldhacker1]

* V12 compatibility. [waldhacker1]

* Fix license. [Ralf Zimmermann]

* Compatibility with Typo3 12. [waldhacker-joerg]

* Revert usage of browser session for site / timeframe selection. [waldhacker1]

* Bump TYPO3 version. [waldhacker1]

* Headers for bar charts. [waldhacker-joerg]

* Restore the last value of Tabs when reloading widgets. [waldhacker-joerg]

* Restore the last value of TimeFrame and Site when reloading the widgets. [waldhacker-joerg]

### Features

* Add class. [waldhacker-joerg]

### Bugfixes

* Fix javascript regarding preconfigured widgets. [waldhacker1]

* Solve Lint error for empty block. [waldhacker-joerg]

* Bug in SCSS. [waldhacker-joerg]

### Other

* Merge pull request #19 from waldhacker/feature/fix-multi-static-configs. [Ralf Zimmermann]

  [BUGFIX] Fix javascript regarding preconfigured widgets

* Merge pull request #16 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  [TASK] Compatibility with TYPO3 v12

* Merge pull request #15 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  [TASK] Revert usage of browser session for site / timeframe selection

* Merge pull request #12 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  Feature/add new widgets

* Merge tag '2.0.0' into develop. [waldhacker1]

  [TASK] Release 2.0.0


## 2.0.0 (2021-11-17)

### Tasks

* Prepare release. [waldhacker1]

* Add new dashboard image. [waldhacker1]

* CSS styling of the fixed site and time frame display. [waldhacker-joerg]

* Add CI status badge. [waldhacker1]

* Turn off tracking script include by default. [waldhacker1]

* Adjust docs. [Ralf Zimmermann]

  Feature/docs

* Add migration notice. [waldhacker1]

* Docs. [waldhacker1]

* Start docs adjustments. [waldhacker1]

* Use docker for changelog generation. [waldhacker1]

* Change meta data. [waldhacker1]

* Fix overview. [waldhacker1]

* CGL fixes. [waldhacker1]

* Re-add predefined sites and time frames. [waldhacker1]

* Add unit tests. [waldhacker1]

* Change widget order. [waldhacker1]

* Fix configuration bug. [waldhacker1]

* Add site selection / refactore code. [waldhacker1]

* Use topojson-client. [waldhacker1]

* CGL fixes. [waldhacker1]

* CI config fixes. [waldhacker1]

* CGL fixes. [waldhacker1]

* Use external package for ISO3166 mappings. [waldhacker1]

* Make phpstan happy. [waldhacker1]

* Add translations / rename components. [waldhacker1]

* Make js and php widget names consistent. [waldhacker1]

* Ensure js loading order. [waldhacker1]

* Move css to scss / tmp disable typescript compilation. [waldhacker1]

* Code clean up. [waldhacker-joerg]

* TimeFrameSelector for each widget. Widget for Devices and Sources implemented. Thus, all widgets and statistical data are implemented. All widgets converted to ajax-only. [waldhacker-joerg]

* TimeFrameSelector for each widget. Pages-views widget and Visitors-over-Time widget now also work with the global TimeFrame-selects. Both widgets have been completely converted to ajax, including the loading of the first data displayed. [waldhacker-joerg]

* TimeFrameSelector for each widget. There were too many defaults for TimeFrame. [waldhacker-joerg]

* TimeFrameSelector for each widget. Data update implemented via a CustomEvent. [waldhacker-joerg]

* Insert a TimeFrameSelector into each widget. If you change the time period in one widget, it changes in all the others as well. At the moment, only the CountryMap is updated when the time span is changed. [waldhacker-joerg]

* Begin move css from code to css file [TASK] Current D3 number formater implemented. The one in the D3 package for datamaps is from 2014 and had some limitations. [waldhacker-joerg]

* Start enhancement of VisitorsWidget by four overview values (Unique visitors, Total pageviews, Visit duration, Current visitors). [waldhacker-joerg]

* Restructuring. Transfer specific widget methods from the PlausibleService to the corresponding DataProvider classes. The PlausibleServiceTest.php still needs to be adapted accordingly. [waldhacker-joerg]

* Migrate ChartServiceTest / some CGL cleanup. [waldhacker1]

* Restructuring. Removing unnecessary services. [waldhacker-joerg]

* Revert template changes. [waldhacker1]

* Change headers. [waldhacker1]

* Load 3th party js libs via npm. [waldhacker1]

* Make linter happy. [waldhacker1]

* Exclude some folders for CGL checks. [waldhacker1]

* Update typo3/coding-standards. [waldhacker1]

* Add asset build scripts. [waldhacker1]

* Apply CGL to HTML files. [waldhacker1]

* Adjust headers. [waldhacker1]

* Restructuring. Removing unnecessary services. [waldhacker-joerg]

* Countries Widget: Adjust the configuration of requirejs so that Datamap.js can load its required modules without having to adjust Datamap's code. [waldhacker-joerg]

* Start wordlmap / pagehit widget implementation. [waldhacker-joerg]

* Adjust README. [waldhacker1]

* Add plausible.io icon. [Ralf Zimmermann]

* Optimize ddev setup / generate html coverage report. [Ralf Zimmermann]

### Bugfixes

* Require.config.path -> file not found. "TYPO3/CMS/" is not resolved correctly. It works properly in require.config.map. Behaviour before the fix: The map is still displayed correctly, but an error message appears in the browser log. [waldhacker-joerg]

  [TASK] SI-prefix for visitors count -> 2560 -> 2.5k
  [TASK] Code style changes

### Other

* Merge branch 'release/2.0.0' into main. [waldhacker1]

* Merge pull request #10 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  [TASK] CSS styling of the fixed site and time frame display

* Merge pull request #9 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  [TASK] Add CI status badge

* Merge pull request #8 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  [TASK] Turn off tracking script include by default

* Merge pull request #7 from waldhacker/feature/add-new-widgets. [Ralf Zimmermann]

  Feature/add new widgets

* Merge pull request #5 from waldhacker/feature/add-site-selector. [Ralf Zimmermann]

  [TASK] Add site selection / refactore code

* Merge branch 'feature/migrate-to-typescript' into feature/add-new-widgets. [waldhacker1]

* Merge branch 'feature/add-new-widgets' into feature/migrate-to-typescript. [waldhacker1]


## 1.0.0 (2021-07-11)

### Tasks

* Prepare release. [Susanne Moog]

* Adjust composer.json. [Susanne Moog]

* Add Docs. [Susanne Moog]

* Show missing configuration error. [Susanne Moog]

* Add local environment. [Susanne Moog]

* Fix CGL. [Susanne Moog]

* Add service tests. [Susanne Moog]

* Github basic setup. [Susanne Moog]

* Add image to readme. [Susanne Moog]

* CI Fixes. [Susanne Moog]

* Initial version. [Susanne Moog]


