<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryDataProvider
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(PlausibleService $plausibleService, ConfigurationService $configurationService)
    {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }

    public function getCountryData(?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();

        $deviceDataApi = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => 'visit:country',
        ];

        $uri = $deviceDataApi . http_build_query($params);

        return $this->plausibleService->sendAuthorizedRequest($uri);
    }
}
