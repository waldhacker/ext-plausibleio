<?php

declare(strict_types=1);

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

namespace Waldhacker\Plausibleio\Dashboard\Widget;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, RequireJsModuleInterface
{
    private PageRenderer $pageRenderer;
    private StandaloneView $view;
    private WidgetConfigurationInterface $configuration;
    private PageDataProvider $dataProvider;
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;
    private array $options;

    public function __construct(
        PageRenderer $pageRenderer,
        StandaloneView $view,
        WidgetConfigurationInterface $configuration,
        PageDataProvider $dataProvider,
        PlausibleService $plausibleService,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->view = $view;
        $this->configuration = $configuration;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
        $this->preparePageRenderer();
    }

    public function renderWidgetContent(): string
    {
        $dashBoardId = $this->plausibleService->getCurrentDashboardId();
        $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration($dashBoardId);

        $this->view->setTemplate('BaseTabs');
        $this->view->assignMultiple([
            'id' => $this->plausibleService->getRandomId('pageDataWidget'),
            'label' => 'widget.pageData.label',
            'configuration' => $this->configuration,
            'validConfiguration' => $this->configurationService->isValidConfiguration($plausibleSiteId),
            'timeSelectorConfig' => [
                'items' => $this->configurationService->getTimeFrames(),
                'selected' => $this->configurationService->getTimeFrameValueFromUserConfiguration($dashBoardId),
            ],
            'siteSelectorConfig' => [
                'items' => $this->configurationService->getAvailablePlausibleSiteIds(),
                'selected' => $plausibleSiteId,
            ],
            'predefinedSiteId' => $this->options['siteId'] ?? null,
            'predefinedTimeFrame' => $this->options['timeFrame'] ?? null,
            'widgetType' => 'pageChart',
            'tabs' => [
                [
                    'label' => 'widget.pageData.tabs.toppage',
                    'id' => 'toppage',
                ],
                [
                    'label' => 'widget.pageData.tabs.entrypage',
                    'id' => 'entrypage',
                ],
                [
                    'label' => 'widget.pageData.tabs.exitpage',
                    'id' => 'exitpage',
                ],
            ],
        ]);

        return $this->view->render();
    }

    public function getEventData(): array
    {
        $dashBoardId = $this->plausibleService->getCurrentDashboardId();

        return [
            'filters' => $this->configurationService->getFiltersFromUserConfiguration($dashBoardId)->getFiltersAsArray(),
        ];
    }

    public function getCssFiles(): array
    {
        return [
            'EXT:plausibleio/Resources/Public/Css/widget.css',
        ];
    }

    public function getRequireJsModules(): array
    {
        return [
            'TYPO3/CMS/Plausibleio/PageDataWidget',
            'TYPO3/CMS/Plausibleio/WidgetService',
        ];
    }

    private function preparePageRenderer(): void
    {
        $this->pageRenderer->addRequireJsConfiguration([
            'config' => [
                'TYPO3/CMS/Plausibleio/WidgetService' => [
                    'lang' => [
                        'noDataAvailable' => $this->getLanguageService()->getLL('noDataAvailable'),
                    ],
                ],
            ],
            'shim' => [
                'TYPO3/CMS/Dashboard/WidgetContentCollector' => [
                    'deps' => [
                        'TYPO3/CMS/Plausibleio/PageDataWidget',
                    ],
                ],
            ],
        ]);
    }

    public function getOptions(): array
    {
        return $this->options;
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
