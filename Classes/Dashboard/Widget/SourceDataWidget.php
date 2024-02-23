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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\JavaScriptInterface;
use TYPO3\CMS\Dashboard\Widgets\RequestAwareWidgetInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class SourceDataWidget implements WidgetInterface, AdditionalCssInterface, JavaScriptInterface, RequestAwareWidgetInterface
{
    private ServerRequestInterface $request;
    private BackendViewFactory $backendViewFactory;
    private WidgetConfigurationInterface $configuration;
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;
    private array $options;

    public function __construct(
        BackendViewFactory $backendViewFactory,
        WidgetConfigurationInterface $configuration,
        PlausibleService $plausibleService,
        ConfigurationService $configurationService,
        array $options = []
    ) {
        $this->backendViewFactory = $backendViewFactory;
        $this->configuration = $configuration;
        $this->options = $options;
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->request = $request;
    }

    public function renderWidgetContent(): string
    {
        $view = $this->backendViewFactory->create($this->request, ['waldhacker/typo3-plausibleio', 'typo3/cms-dashboard']);
        $plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration();

        $view->assignMultiple([
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
                    'header' => [
                        'barChart.labels.source',
                        'barChart.labels.visitors',
                    ],
                ],
                [
                    'label' => 'widget.sourceData.tabs.mediumsource',
                    'id' => 'mediumsource',
                    'header' => [
                        'barChart.labels.UTMMedium',
                        'barChart.labels.visitors',
                    ],
                ],
                [
                    'label' => 'widget.sourceData.tabs.sourcesource',
                    'id' => 'sourcesource',
                    'header' => [
                        'barChart.labels.UTMSource',
                        'barChart.labels.visitors',
                    ],
                ],
                [
                    'label' => 'widget.sourceData.tabs.campaignsource',
                    'id' => 'campaignsource',
                    'header' => [
                        'barChart.labels.UTMCampaign',
                        'barChart.labels.visitors',
                    ],
                ],
            ],
        ]);

        return $view->render('Widgets/BaseTabs');
    }

    public function getCssFiles(): array
    {
        return [
            'EXT:plausibleio/Resources/Public/Css/widget.css',
        ];
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::create('@typo3/dashboard/contrib/chartjs.js'),
            JavaScriptModuleInstruction::create('@typo3/dashboard/chart-initializer.js'),
            JavaScriptModuleInstruction::create('@typo3/dashboard/widget-content-collector.js'),
            JavaScriptModuleInstruction::create('@waldhacker/plausibleio/source-data-widget.js'),
            JavaScriptModuleInstruction::create('@waldhacker/plausibleio/widget-service.js'),
        ];
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
