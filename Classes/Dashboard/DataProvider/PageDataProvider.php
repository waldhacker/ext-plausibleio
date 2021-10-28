<?php

declare(strict_types=1);

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataProvider
{
    private PlausibleService $plausibleService;
    private ConfigurationService $configurationService;

    public function __construct(PlausibleService $plausibleService, ConfigurationService $configurationService)
    {
        $this->plausibleService = $plausibleService;
        $this->configurationService = $configurationService;
    }


    private function getPageData(string $property, ?string $timeFrame = null, ?string $site = null): array
    {
        $timeFrame = $timeFrame ?? $this->configurationService->getDefaultTimeFrameValue();
        $site = $site ?? $this->configurationService->getDefaultSite();

        $deviceDataApi = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $site,
            'period' => $timeFrame,
            'property' => $property
        ];

        $uri = $deviceDataApi . http_build_query($params);

        return $this->plausibleService->sendAuthorizedRequest($uri);
    }

    public function getTopPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('event:page', $timeFrame, $site);
    }

    public function getEntryPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('visit:entry_page', $timeFrame, $site);
    }

    public function getExitPageData(?string $timeFrame = null, ?string $site = null): array
    {
        return $this->getPageData('visit:exit_pag', $timeFrame, $site);
    }
}
