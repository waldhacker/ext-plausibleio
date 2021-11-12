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
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataWidget implements WidgetInterface, RequireJsModuleInterface, AdditionalCssInterface
{
    private PageRenderer $pageRenderer;
    private StandaloneView $view;
    private WidgetConfigurationInterface $configuration;
    private CountryMapDataProvider $dataProvider;
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;
    private array $options;

    public function __construct(
        PageRenderer $pageRenderer,
        StandaloneView $view,
        WidgetConfigurationInterface $configuration,
        CountryMapDataProvider $dataProvider,
        PlausibleService $plausibleService,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->pageRenderer = $pageRenderer;
        $this->view = $view;
        $this->configuration = $configuration;
        $this->dataProvider = $dataProvider;
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
        $this->options = $options;
        $this->preparePageRenderer();
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('CountryMapDataWidget');
        $this->view->assignMultiple([
            'id' => $this->plausibleService->getRandomId('countryMapDataWidget'),
            'label' => 'widget.countryMapData.label',
            'options' => $this->options,
            'configuration' => $this->configuration,
            'validConfiguration' => $this->configurationService->isValidConfiguration(),
            'timeSelectorConfig' => [
                'items' => $this->configurationService->getTimeFrames(),
                'selected' => $this->configurationService->getDefaultTimeFrameValue(),
            ],
        ]);

        return $this->view->render();
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
            'TYPO3/CMS/Plausibleio/CountryMapDataWidget',
            'TYPO3/CMS/Plausibleio/Contrib/topojson.min',
            'TYPO3/CMS/Plausibleio/Contrib/datamaps.world.min',
            'TYPO3/CMS/Plausibleio/Contrib/d3-format',
            'TYPO3/CMS/Plausibleio/WidgetService',
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
                'shim' => [
                    'TYPO3/CMS/Dashboard/WidgetContentCollector' => [
                        'deps' => [
                            'TYPO3/CMS/Plausibleio/CountryMapDataWidget',
                        ],
                    ],
                ],
            ]
        );
    }
}
