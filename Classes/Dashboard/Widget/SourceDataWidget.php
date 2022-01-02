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

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class SourceDataWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, RequireJsModuleInterface
{
    private PageRenderer $pageRenderer;
    private StandaloneView $view;
    private WidgetConfigurationInterface $configuration;
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;
    private array $options;

    public function __construct(
        PageRenderer $pageRenderer,
        StandaloneView $view,
        WidgetConfigurationInterface $configuration,
        PlausibleService $plausibleService,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->view = $view;
        $this->configuration = $configuration;
        $this->options = $options;
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
        $this->preparePageRenderer();
    }

    public function renderWidgetContent(): string
    {
        $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration();

        $this->view->setTemplate('BaseTabs');
        $this->view->assignMultiple([
            'id' => $this->plausibleService->getRandomId('sourceDataWidget'),
            'label' => 'widget.sourceData.label',
            'configuration' => $this->configuration,
            'validConfiguration' => $this->configurationService->isValidConfiguration($plausibleSiteId),
            'timeSelectorConfig' => [
                'items' => $this->configurationService->getTimeFrames(),
                'selected' => $this->configurationService->getTimeFrameValueFromUserConfiguration(),
            ],
            'siteSelectorConfig' => [
                'items' => $this->configurationService->getAvailablePlausibleSiteIds(),
                'selected' => $plausibleSiteId,
            ],
            'predefinedSiteId' => $this->options['siteId'] ?? null,
            'predefinedTimeFrame' => $this->options['timeFrame'] ?? null,
            'widgetType' => 'sourceChart',
            'tabs' => [
                [
                    'label' => 'widget.sourceData.tabs.allsources',
                    'id' => 'allsources',
                ],
                [
                    'label' => 'widget.sourceData.tabs.mediumsource',
                    'id' => 'mediumsource',
                ],
                [
                    'label' => 'widget.sourceData.tabs.sourcesource',
                    'id' => 'sourcesource',
                ],
                [
                    'label' => 'widget.sourceData.tabs.campaignsource',
                    'id' => 'campaignsource',
                ],
                [
                    'label' => 'widget.sourceData.tabs.termsource',
                    'id' => 'termsource',
                ],
                [
                    'label' => 'widget.sourceData.tabs.contentsource',
                    'id' => 'contentsource',
                ],
            ],
        ]);

        return $this->view->render();
    }

    public function getEventData(): array
    {
        return [
            'filters' => $this->configurationService->getAllFiltersFromUserConfiguration(),
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
            'TYPO3/CMS/Plausibleio/SourceDataWidget',
            'TYPO3/CMS/Plausibleio/WidgetService',
        ];
    }

    private function preparePageRenderer(): void
    {
        $this->pageRenderer->addRequireJsConfiguration([
            'shim' => [
                'TYPO3/CMS/Dashboard/WidgetContentCollector' => [
                    'deps' => [
                        'TYPO3/CMS/Plausibleio/SourceDataWidget',
                    ],
                ],
            ],
        ]);
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
