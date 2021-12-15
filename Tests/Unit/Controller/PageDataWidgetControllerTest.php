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
use Waldhacker\Plausibleio\Controller\PageDataWidgetController;
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataWidgetControllerTest extends UnitTestCase
{
    use ProphecyTrait;

    public function controllerProcessesValidAndInvalidUserInputCorrectlyDataProvider(): \Generator
    {
        yield 'Valid userinput is processed' => [
            'queryParameters' => ['siteId' => 'site1', 'timeFrame' => 'day', 'filters' => []],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site1',
            'expectedTimeFrame' => 'day',
            'expectedFilters' => [],
        ];

        yield 'Invalid site is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site9', 'timeFrame' => 'day', 'filters' => []],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site4',
            'expectedTimeFrame' => 'day',
            'expectedFilters' => [],
        ];

        yield 'Invalid time frame is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site1', 'timeFrame' => 'minute', 'filters' => []],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site1',
            'expectedTimeFrame' => '12mo',
            'expectedFilters' => [],
        ];

        yield 'Invalid site and time frame is ignored and the value from the user configuration is used instead' => [
            'queryParameters' => ['siteId' => 'site9', 'timeFrame' => 'minute', 'filters' => []],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site4',
            'expectedTimeFrame' => '12mo',
            'expectedFilters' => [],
        ];

        yield 'No filters are passed in the ServerRequest' => [
            'queryParameters' => ['siteId' => 'site1', 'timeFrame' => 'day'],
            'availablePlausibleSiteIds' => ['site1', 'site2', 'site3', 'site4'],
            'timeFrameValues' => ['day', '7d', '30d', '12mo'],
            'siteIdFromConfiguration' => 'site4',
            'timeFrameFromConfiguration' => '12mo',
            'expectedSiteId' => 'site1',
            'expectedTimeFrame' => 'day',
            'expectedFilters' => [],
        ];
    }

    /**
     * @test
     * @dataProvider controllerProcessesValidAndInvalidUserInputCorrectlyDataProvider
     * @covers \Waldhacker\Plausibleio\Controller\PageDataWidgetController::__construct
     * @covers \Waldhacker\Plausibleio\Controller\PageDataWidgetController::__invoke
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
        $pageDataProviderProphecy = $this->prophesize(PageDataProvider::class);
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
        $configurationServiceProphecy->getPlausibleSiteIdFromUserConfiguration()->willReturn($siteIdFromConfiguration);
        $configurationServiceProphecy->getTimeFrameValueFromUserConfiguration()->willReturn($timeFrameFromConfiguration);

        $pageDataProviderProphecy->getTopPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['toppage' => 'data']);
        $pageDataProviderProphecy->getEntryPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['entrypage' => 'data']);
        $pageDataProviderProphecy->getExitPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->willReturn(['exitpage' => 'data']);

        $configurationServiceProphecy->persistPlausibleSiteIdInUserConfiguration($expectedSiteId)->shouldBeCalled();
        $configurationServiceProphecy->persistTimeFrameValueInUserConfiguration($expectedTimeFrame)->shouldBeCalled();
        $pageDataProviderProphecy->getTopPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->shouldBeCalled();
        $pageDataProviderProphecy->getEntryPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->shouldBeCalled();
        $pageDataProviderProphecy->getExitPageData($expectedSiteId, $expectedTimeFrame, $expectedFilters)->shouldBeCalled();

        $plausibleServiceProphecy->checkFilters($expectedFilters)->willReturn([]);
        $plausibleServiceProphecy->checkFilters($expectedFilters)->shouldBeCalled();

        $streamProphecy->write('[{"tab":"toppage","data":{"toppage":"data"}},{"tab":"entrypage","data":{"entrypage":"data"}},{"tab":"exitpage","data":{"exitpage":"data"}}]')->shouldBeCalled();

        $subject = new PageDataWidgetController(
            $pageDataProviderProphecy->reveal(),
            $configurationServiceProphecy->reveal(),
            $plausibleServiceProphecy->reveal(),
            $responseFactoryProphecy->reveal()
        );

        $subject->__invoke($serverRequestProphecy->reveal());
    }
}
