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

namespace Waldhacker\Plausibleio\Tests\Unit\Dashboard\DataProvider;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class VisitorsOverTimeDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    protected function tearDown(): void
    {
        $GLOBALS['LANG'] = null;

        parent::tearDown();
    }

    public function getAllSourcesDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'expected' => [
                'bounce_rate' => 1,
                'pageviews' => 2,
                'visit_duration' => 3,
                'visitors' => 4
            ],
        ];

        yield 'all items are transformed with filters' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'expected' => [
                'bounce_rate' => 1,
                'pageviews' => 2,
                'visit_duration' => 3,
                'visitors' => 4
            ],
        ];

        yield 'items without bounce_rate are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'expected' => [],
        ];

        yield 'items without pageviews are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'visit_duration' => ['value' => 3],
                'visitors' => ['value' => 4],
            ],
            'expected' => [],
        ];

        yield 'items without visit_duration are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visitors' => ['value' => 4],
            ],
            'expected' => [],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                'bounce_rate' => ['value' => 1],
                'pageviews' => ['value' => 2],
                'visit_duration' => ['value' => 3],
            ],
            'expected' => [],
        ];
    }

    /**
     * @test
     * @dataProvider getAllSourcesDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getOverview
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getAllSourcesDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors,visit_duration,pageviews,bounce_rate',
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/aggregate?',
            $authorizedRequestParams
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOverview($plausibleSiteId, $timeFrame, $filters));
    }

    public function getCurrentVisitorsReturnsVisitorsDataProvider(): \Generator
    {
        yield 'integers from API will be returned' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [],
            'endpointData' => 2,
            'expected' => 2,
        ];

        yield 'integers from API will be returned with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => 2,
            'expected' => 2,
        ];

        yield 'non integers from API will be returned as 0' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'filters' => [],
            'endpointData' => null,
            'expected' => 0,
        ];
    }

    /**
     * @test
     * @dataProvider getCurrentVisitorsReturnsVisitorsDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getCurrentVisitors
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getCurrentVisitorsReturnsVisitors(
        string $plausibleSiteId,
        array $filters,
        ?int $endpointData,
        int $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            '/api/v1/stats/realtime/visitors?',
            $authorizedRequestParams
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getCurrentVisitors($plausibleSiteId, $filters));
    }

    public function getChartDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    '2021-05-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    '2021-05-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'items without date are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                    ''
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3, 4],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['date' => '2021-04-16', 'visitors' => 3],
                ['date' => '2021-05-16', 'visitors' => null],
                ['date' => '2021-05-16'],
            ],
            'expected' => [
                'labels' => [
                    '2021-04-16',
                ],
                'datasets' => [
                    [
                        'label' => 'Visitors',
                        'data' => [3],
                        'fill' => false,
                        'borderColor' => '#85bcee',
                        'tension' => 0.5,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getChartDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getChartData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getVisitors
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\VisitorsOverTimeDataProvider::getLanguageService
     */
    public function getChartDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        array $expected
    ): void {
        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();
        $languageServiceProphecy->includeLLFile('EXT:plausibleio/Resources/Private/Language/locallang.xlf')->shouldBeCalled();
        $languageServiceProphecy->getLL('visitors')->willReturn('Visitors');

        if ($filters) {
            $plausibleServiceProphecy->filtersToPlausibleFilterString($filters)->willReturn($filters[0]['name']);
        }
        $plausibleServiceProphecy->filtersToPlausibleFilterString([])->willReturn('');

        $authorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
        ];
        if ($filters) {
            $authorizedRequestParams['filters'] = $filters[0]['name'];
        }

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/timeseries?',
            $authorizedRequestParams
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new VisitorsOverTimeDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getChartData($plausibleSiteId, $timeFrame, $filters));
    }
}
