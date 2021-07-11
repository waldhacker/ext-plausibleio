<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Dashboard\Widget;

use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Dashboard\Widgets\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use TYPO3\CMS\Dashboard\Widgets\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\RequireJsModuleInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class VisitorsOverTime implements WidgetInterface, EventDataInterface, AdditionalCssInterface, RequireJsModuleInterface
{
    private ChartDataProviderInterface $dataProvider;
    private StandaloneView $view;
    private WidgetConfigurationInterface $configuration;
    private array $options;
    private ConfigurationService $configurationService;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        ChartDataProviderInterface $dataProvider,
        StandaloneView $view,
        ConfigurationService $configurationService,
        PageRenderer $pageRenderer,
        array $options = []
    ) {
        $pageRenderer->addInlineLanguageLabelFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf');
        $this->dataProvider = $dataProvider;
        $this->view = $view;
        $this->configuration = $configuration;
        $this->options = $options;
        $this->configurationService = $configurationService;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('ChartWidget');
        $this->view->assignMultiple(
            [
                'configuration' => $this->configuration,
                'validConfiguration' => $this->configurationService->isValidConfiguration(),
            ]
        );
        return $this->view->render();
    }

    public function getEventData(): array
    {
        return [
            'selectorConfig' => $this->configurationService->getTimeFrames(),
            'site' => $this->options['siteId'] ?? $this->configurationService->getDefaultSite(),
            'graphConfig' => [
                'type' => 'line',
                'options' => [
                    'maintainAspectRatio' => false,
                ],
                'data' => $this->dataProvider->getChartData(),
            ],
        ];
    }

    public function getCssFiles(): array
    {
        return ['EXT:dashboard/Resources/Public/Css/Contrib/chart.css'];
    }

    public function getRequireJsModules(): array
    {
        return [
            'TYPO3/CMS/Dashboard/Contrib/chartjs',
            'TYPO3/CMS/Dashboard/ChartInitializer',
            'TYPO3/CMS/Plausibleio/VisitorLoader',
        ];
    }
}
