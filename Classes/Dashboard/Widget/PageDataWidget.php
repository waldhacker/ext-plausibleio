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

use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class PageDataWidget implements WidgetInterface, AdditionalCssInterface, RequireJsModuleInterface
{
    private WidgetConfigurationInterface $configuration;
    private StandaloneView $view;
    private array $options;
    private PageDataProvider $dataProvider;
    private ConfigurationService $configurationService;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        PageDataProvider $dataProvider,
        StandaloneView $view,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
        $this->configurationService = $configurationService;
    }

    private function getTopPages(): array
    {
        $items = $this->dataProvider->getTopPageData(
            $this->options['timeFrame'],
            $this->options['siteId']
        );
        $sum = 0;

        foreach ($items as $item) {
            $sum += $item->visitors;
        }

        return ['items' => $items, 'sum' => $sum];
    }

    private function getEntryPages(): array
    {
        $items = $this->dataProvider->getEntryPageData(
            $this->options['timeFrame'],
            $this->options['siteId']
        );
        $sum = 0;

        foreach ($items as $item) {
            $sum += $item->visitors;
        }

        return ['items' => $items, 'sum' => $sum];
    }

    private function getExitPages(): array
    {
        $items = $this->dataProvider->getExitPageData(
            $this->options['timeFrame'],
            $this->options['siteId']
        );
        $sum = 0;

        foreach ($items as $item) {
            $sum += $item->visitors;
        }

        return ['items' => $items, 'sum' => $sum];
    }

    public function renderWidgetContent(): string
    {
        $tabsData = [
            [
                'label' => 'Top Pages',
                'id' => 'toppage',
            ],
            [
                'label' => 'Entry Pages',
                'id' => 'entrypage',
            ],
            [
                'label' => 'Exit Pages',
                'id' => 'exitpage',
            ],
        ];
        $timeSelectorConfig = [
            'items' => $this->configurationService->getTimeFrames(),
            'selected' => $this->configurationService->getDefaultTimeFrameValue(),
        ];

        $this->view->setTemplate('BaseTabs');
        $this->view->assignMultiple([
                                        'widgetType' => 'pageChart',
                                        'timeSelectorConfig' => $timeSelectorConfig,
                                        'tabs' => $tabsData,
                                        'id' => 'plausibleWidgteTab-' . bin2hex(random_bytes(8)),
                                        'options' => $this->options,
                                        'configuration' => $this->configuration,
                                        'label' => 'plausible.pageData.label',
                                        'validConfiguration' => $this->configurationService->isValidConfiguration(),
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
            'TYPO3/CMS/Plausibleio/Contrib/d3-format',
            'TYPO3/CMS/Plausibleio/PageLoader',
            'TYPO3/CMS/Plausibleio/PlausibleWidgets',
        ];
    }
}
