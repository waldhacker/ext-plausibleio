<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;
use Waldhacker\Plausibleio\Services\ChartService;
use Waldhacker\Plausibleio\Services\ConfigurationService;

class TimeSeriesDataProvider implements ChartDataProviderInterface
{
    private ChartService $chartService;
    private ConfigurationService $configurationService;

    public function __construct(ChartService $chartService, ConfigurationService $configurationService)
    {
        $this->chartService = $chartService;
        $this->configurationService = $configurationService;
    }

    public function getChartData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->chartService->getChartDataForTimeSeries(
            $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue(),
            $site ?? $this->configurationService->getDefaultSite()
        );
    }
}
