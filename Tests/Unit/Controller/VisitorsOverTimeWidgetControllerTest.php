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

namespace Waldhacker\Plausibleio\Tests\Unit\Controller;

use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Controller\VisitorsOverTimeWidgetController;
use Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class VisitorsOverTimeWidgetControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    public function controllerProcessesValidAndInvalidUserInputCorrectlyDataProvider(): \Generator
    {
        yield 'Valid userinput is processed' => [
            'queryParameters' => ['siteId' => 'site1', 'timeFrame' => 'day'],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site1',
            'expectedTimeFrame' => 'day',
            'expectedFilters' => [],
        ];

        yield 'Invalid site is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site9', 'timeFrame' => 'day'],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site4',
            'expectedTimeFrame' => 'day',
            'expectedFilters' => [],
        ];

        yield 'Invalid time frame is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site1', 'timeFrame' => 'minute'],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site1',
            'expectedTimeFrame' => '12mo',
            'expectedFilters' => [],
        ];

        yield 'Invalid site and time frame is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site9', 'timeFrame' => 'minute'],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site4',
            'expectedTimeFrame' => '12mo',
            'expectedFilters' => [],
        ];
    }

    /**
     * @test
     * @dataProvider controllerProcessesValidAndInvalidUserInputCorrectlyDataProvider
     * @covers \Waldhacker\Plausibleio\Controller\VisitorsOverTimeWidgetController::__construct
     * @covers \Waldhacker\Plausibleio\Controller\VisitorsOverTimeWidgetController::__invoke
     */
    public function controllerProcessesValidAndInvalidUserInputCorrectly(
        array $queryParameters,
        array $availablePlausibleSiteIds,
        array $timeFrameValues,
        string $siteIdFromConfiguration,
        string $timeFrameFromConfiguration,
        string $expectedSiteId,
        string $expectedTimeFrame,
        array $expectedFilters
    ): void {
        $serverRequestProphecy = $this->prophesize(ServerRequestInterface::class);
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $visitorsOverTimeDataProviderProphecy = $this->prophesize(VisitorsOverTimeDataProvider::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $responseFactoryProphecy = $this->prophesize(ResponseFactoryInterface::class);
        $responseProphecy = $this->prophesize(ResponseInterface::class);
        $streamProphecy = $this->prophesize(StreamInterface::class);

        $responseFactoryProphecy->createResponse(200)->willReturn($responseProphecy->reveal());
        $responseProphecy->withHeader('Content-Type', 'application/json')->willReturn($responseProphecy->reveal());
        $responseProphecy->getBody()->willReturn($streamProphecy->reveal());

        $serverRequestProphecy->getQueryParams()->willReturn($queryParameters);
        $configurationServiceProphecy->getAvailablePlausibleSiteIds()->willReturn($availablePlausibleSiteIds);
        $configurationServiceProphecy->getTimeFrameValues()->willReturn($timeFrameValues);
        $configurationServiceProphecy->getPlausibleSiteIdFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID)->willReturn($siteIdFromConfiguration);
        $configurationServiceProphecy->getTimeFrameValueFromUserConfiguration(ConfigurationService::DASHBOARD_DEFAULT_ID)->willReturn($timeFrameFromConfiguration);

        $visitorsOverTimeDataProviderProphecy->getChartData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['chart' => 'data']);
        $visitorsOverTimeDataProviderProphecy->getOverview($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['overview' => 'data']);
        $visitorsOverTimeDataProviderProphecy->getCurrentVisitors($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['visitors' => 'data']);

        $configurationServiceProphecy->persistPlausibleSiteIdInUserConfiguration($expectedSiteId, ConfigurationService::DASHBOARD_DEFAULT_ID)->shouldBeCalled();
        $configurationServiceProphecy->persistTimeFrameValueInUserConfiguration($expectedTimeFrame, ConfigurationService::DASHBOARD_DEFAULT_ID)->shouldBeCalled();
        $configurationServiceProphecy->persistFiltersInUserConfiguration($expectedFilters, ConfigurationService::DASHBOARD_DEFAULT_ID)->shouldBeCalled();
        $visitorsOverTimeDataProviderProphecy->getChartData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->shouldBeCalled();
        $visitorsOverTimeDataProviderProphecy->getOverview($expectedSiteId, $expectedTimeFrame, $expectedFilters)->shouldBeCalled();

        $plausibleServiceProphecy->checkFilters($expectedFilters)->willReturn([]);
        $plausibleServiceProphecy->checkFilters($expectedFilters)->shouldBeCalled();

        $streamProphecy->write('{"chartData":{"chart":"data"},"overViewData":{"overview":"data"}}')->shouldBeCalled();

        $subject = new VisitorsOverTimeWidgetController(
            $visitorsOverTimeDataProviderProphecy->reveal(),
            $configurationServiceProphecy->reveal(),
            $plausibleServiceProphecy->reveal(),
            $responseFactoryProphecy->reveal()
        );

        $subject->__invoke($serverRequestProphecy->reveal());
    }
}
