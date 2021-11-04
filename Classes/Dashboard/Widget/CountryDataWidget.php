<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Plausibleio\Dashboard\Widget;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Dashboard\DataProvider\CountryDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class CountryDataWidget implements WidgetInterface, RequireJsModuleInterface, EventDataInterface
{
    private PageRenderer $pageRenderer;
    private WidgetConfigurationInterface $configuration;
    private StandaloneView $view;
    private array $options;
    private CountryDataProvider $dataProvider;
    private ConfigurationService $configurationService;
    private string $mapElementId = '';

    public function __construct(
        PageRenderer $pageRenderer,
        WidgetConfigurationInterface $configuration,
        CountryDataProvider $dataProvider,
        StandaloneView $view,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
        $this->configurationService = $configurationService;

        $this->mapElementId = 'plausibleWidgetWorldMap-' . bin2hex(random_bytes(8));
        $this->preparePageRenderer();
    }

    public function renderWidgetContent(): string
    {
        $timeSelectorConfig = [
            'items' => $this->configurationService->getTimeFrames(),
            'selected' => $this->configurationService->getDefaultTimeFrameValue(),
        ];

        $this->view->setTemplate('CountryMap');
        $this->view->assignMultiple([
            'id' => $this->mapElementId,
            'options' => $this->options,
            'timeSelectorConfig' => $timeSelectorConfig,
            'configuration' => $this->configuration,
            'label' => 'plausible.countryData.label',
            'validConfiguration' => $this->configurationService->isValidConfiguration(),
        ]);

        return $this->view->render();
    }

    public function getEventData(): array
    {
        /*
        $data = $this->dataProvider->getCountryDataForDataMap($this->options['timeFrame'], $this->options['siteId']);
        return [
            'widgetId' => $this->mapElementId,
            'data' => $data,
        ];
        */
        return [];
    }

    public function getRequireJsModules(): array
    {
        return [
            'TYPO3/CMS/Plausibleio/PlausibleWidgets',
            'TYPO3/CMS/Plausibleio/CountriesLoader',
            'TYPO3/CMS/Plausibleio/Contrib/topojson.min',
            'TYPO3/CMS/Plausibleio/Contrib/datamaps.world.min',
        ];
    }

    private function preparePageRenderer(): void
    {
        $publicResourcesPath = PathUtility::getPublicResourceWebPath('EXT:plausibleio/Resources/Public/');
        $this->pageRenderer->addRequireJsConfiguration(
            [
                'paths' => [
                    'datamaps' => $publicResourcesPath . 'JavaScript/Contrib/datamaps.world.min',
                ],
                'map' => [
                    '*' => [
                        'd3' => 'TYPO3/CMS/Plausibleio/Contrib/d3.min',
                        'topojson' => 'TYPO3/CMS/Plausibleio/Contrib/topojson.min',
                    ],
                ],
            ]
        );
    }
}
