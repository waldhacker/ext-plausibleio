<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Waldhacker\Plausibleio\FilterRepository;
use Waldhacker\Plausibleio\Services\ConfigurationService;

abstract class AbstractWidgetController
{
    protected ConfigurationService $configurationService;
    protected ResponseFactoryInterface $responseFactory;
    protected $dashBoardId = ConfigurationService::DASHBOARD_DEFAULT_ID;
    protected string $plausibleSiteId = '';
    protected string $timeFrame = '';
    protected FilterRepository $filterRepo;

    public function __construct(
        ConfigurationService $configurationService,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->configurationService = $configurationService;
        $this->responseFactory = $responseFactory;
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->dashBoardId = $request->getQueryParams()['dashboard'] ?? ConfigurationService::DASHBOARD_DEFAULT_ID;

        $this->plausibleSiteId = $request->getQueryParams()['siteId'] ?? null;
        if ($this->plausibleSiteId === null || !in_array($this->plausibleSiteId, $this->configurationService->getAvailablePlausibleSiteIds(), true)) {
            $this->plausibleSiteId = $this->configurationService->getPlausibleSiteIdFromUserConfiguration($this->dashBoardId);
        }

        $this->timeFrame = $request->getQueryParams()['timeFrame'] ?? null;
        if ($this->timeFrame === null || !in_array($this->timeFrame, $this->configurationService->getTimeFrameValues(), true)) {
            $this->timeFrame = $this->configurationService->getTimeFrameValueFromUserConfiguration($this->dashBoardId);
        }

        // request->getQueryParams() already returns a json decoded array
        $filters = $request->getQueryParams()['filter'] ?? [];
        if (!is_array($filters)) {
            $filters = [];
        }
        $this->filterRepo = new FilterRepository();
        // At the moment, setFiltersFromArray removes a standalone custom property
        // filter because they are not supported by Plausible. See for this
        // FilterRepository->setFiltersFromArray
        $this->filterRepo->setFiltersFromArray($filters);

        $this->configurationService->persistPlausibleSiteIdInUserConfiguration($this->plausibleSiteId, $this->dashBoardId);
        $this->configurationService->persistTimeFrameValueInUserConfiguration($this->timeFrame, $this->dashBoardId);
        $this->configurationService->persistFiltersInUserConfiguration($this->filterRepo, $this->dashBoardId);

        return $this->responseFactory->createResponse(200);
    }
}
