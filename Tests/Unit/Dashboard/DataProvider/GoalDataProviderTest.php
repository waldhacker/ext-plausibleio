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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\FilterRepository;
use Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class GoalDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $languageServiceProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();
    }

    public function getGoalsDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5],
                ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'getGoalPropertiesData' => [],
            'expected' => [
                'data' => [
                    ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                    ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'goal',
                        'label' => 'Goals',
                        'filter' => [
                            'name' => 'event:goal',
                            'label' => 'Completed goal is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Uniques',
                    ],
                    [
                        'name' => 'events',
                        'label' => 'Total',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'CR',
                    ],
                ],
            ],
        ];

        yield 'all items are transformed with goal filter filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [['name' => FilterRepository::FILTEREVENTGOAL, 'value' => '404']],
            'endpointData' => [
                ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'getGoalPropertiesData' => [
                [
                    'data' => [
                        ['path' => '/end/point', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                        ['path' => '/exit', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                    ],
                    'columns' => [
                        [
                            'name' => 'path',
                            'filter' => [
                                'name' => 'event:props:path',
                                'label' => 'Goals property is',
                            ],
                        ],
                        ['name' => 'visitors'],
                        ['name' => 'events'],
                        ['name' => 'cr'],
                    ],
                ],
            ],
            'expected' => [
                'data' => [
                    [
                        'goal' => 'Mordor',
                        'visitors' => 10,
                        'events' => 6,
                        'percentage' => 100,
                        'cr' => '50%',
                        'subData' => [
                            [
                                'data' => [
                                    ['path' => '/end/point', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                                    ['path' => '/exit', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                                ],
                                'columns' => [
                                    [
                                        'name' => 'path',
                                        'filter' => [
                                            'name' => 'event:props:path',
                                            'label' => 'Goals property is',
                                        ],
                                    ],
                                    ['name' => 'visitors'],
                                    ['name' => 'events'],
                                    ['name' => 'cr'],
                                ],
                            ],
                        ],
                    ],
                ],
                'columns' => [
                    [
                        'name' => 'goal',
                        'label' => 'Goals',
                        'filter' => [
                            'name' => 'event:goal',
                            'label' => 'Completed goal is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Uniques',
                    ],
                    [
                        'name' => 'events',
                        'label' => 'Total',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'CR',
                    ],
                ],
            ],
        ];

        yield 'items without goal are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5],
                ['goal' => '', 'visitors' => 10, 'events' => 6],
                ['goal' => null, 'visitors' => 10, 'events' => 6],
                ['visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'getGoalPropertiesData' => [],
            'expected' => [
                'data' => [
                    ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                    ['goal' => '', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'goal',
                        'label' => 'Goals',
                        'filter' => [
                            'name' => 'event:goal',
                            'label' => 'Completed goal is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Uniques',
                    ],
                    [
                        'name' => 'events',
                        'label' => 'Total',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'CR',
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5],
                ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6],
                ['goal' => 'End', 'visitors' => null, 'events' => 6],
                ['goal' => 'Start', 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'getGoalPropertiesData' => [],
            'expected' => [
                'data' => [
                    ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                    ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'goal',
                        'label' => 'Goals',
                        'filter' => [
                            'name' => 'event:goal',
                            'label' => 'Completed goal is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Uniques',
                    ],
                    [
                        'name' => 'events',
                        'label' => 'Total',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'CR',
                    ],
                ],
            ],
        ];

        yield 'items without events are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'endpointData' => [
                ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5],
                ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6],
                ['goal' => 'End', 'visitors' => 6, 'events' => null],
                ['goal' => 'Start', 'visitors' => 10],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'getGoalPropertiesData' => [],
            'expected' => [
                'data' => [
                    ['goal' => 'Happy ending', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                    ['goal' => 'Mordor', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                ],
                'columns' => [
                    [
                        'name' => 'goal',
                        'label' => 'Goals',
                        'filter' => [
                            'name' => 'event:goal',
                            'label' => 'Completed goal is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Uniques',
                    ],
                    [
                        'name' => 'events',
                        'label' => 'Total',
                    ],
                    [
                        'name' => 'cr',
                        'label' => 'CR',
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getGoalsDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::getGoalsData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRate
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     * @covers \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFilterValue
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
 */
    public function getGoalsDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        array $endpointData,
        array $totalVisitorData,
        array $getGoalPropertiesData,
        array $expected
    ): void {
        $this->languageServiceProphecy->getLL('barChart.labels.goal')->willReturn('Goals');
        $this->languageServiceProphecy->getLL('filter.goalData.goalIs')->willReturn('Completed goal is');
        $this->languageServiceProphecy->getLL('barChart.labels.uniques')->willReturn('Uniques');
        $this->languageServiceProphecy->getLL('barChart.labels.total')->willReturn('Total');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $sendAuthorizedRequestParams = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => 'event:goal',
            'metrics' => 'visitors,events',
        ];
        if (!$filterRepo->empty()) {
            $sendAuthorizedRequestParams['filters'] = $filterRepo->toPlausibleFilterString();
        }

        $plausibleServiceProphecy
            ->sendAuthorizedRequest($plausibleSiteId, 'api/v1/stats/breakdown?', $sendAuthorizedRequestParams)
            ->willReturn($endpointData);
        // for calcConversionRate
        $plausibleServiceProphecy
            ->sendAuthorizedRequest(
                $plausibleSiteId,
                '/api/v1/stats/aggregate?',
                [
                    'site_id' => $plausibleSiteId,
                    'period' => $timeFrame,
                    'metrics' => 'visitors',
                ])
            ->willReturn($totalVisitorData);

        $subject = $this->getMockBuilder(GoalDataProvider::class)
            ->onlyMethods(['getGoalPropertiesData'])
            ->setConstructorArgs([$plausibleServiceProphecy->reveal()])
            ->getMock();

        $subject->expects($filterRepo->isFilterActivated(FilterRepository::FILTEREVENTGOAL) ? $this->once() : $this->never())
        ->method('getGoalPropertiesData')
        ->with(
            $filterRepo->getFilterValue(FilterRepository::FILTEREVENTGOAL),
            $plausibleSiteId,
            $timeFrame,
            $filterRepo
        )
        ->willReturn($getGoalPropertiesData);

        self::assertSame($expected, $subject->getGoalsData($plausibleSiteId, $timeFrame, $filterRepo));
    }

    public function getGoalPropertiesDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '404',
            'property' => ['path'],
            'endpointData' => [
                ['path' => '/end/point', 'visitors' => 6, 'events' => 5],
                ['path' => '/exit', 'visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'expected' => [
                [
                    'data' => [
                        ['path' => '/end/point', 'visitors' => 6, 'events' => 5, 'percentage' => 37.5, 'cr' => '30%'],
                        ['path' => '/exit', 'visitors' => 10, 'events' => 6, 'percentage' => 62.5, 'cr' => '50%'],
                    ],
                    'columns' => [
                        [
                            'name' => 'path',
                            'filter' => [
                                'name' => 'event:props:path',
                                'label' => 'Goals property is',
                            ],
                        ],
                        ['name' => 'visitors'],
                        ['name' => 'events'],
                        ['name' => 'cr'],
                    ],
                ],
            ],
        ];

        yield 'no goal leads to empty result' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '',
            'property' => [],
            'endpointData' => [],
            'totalVisitorData' => [],
            'expected' => [],
        ];

        yield 'no property leads to empty result' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '404',
            'property' => [],
            'endpointData' => [],
            'totalVisitorData' => [],
            'expected' => [],
        ];

        yield 'items without goal field will be skipped' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '404',
            'property' => ['path'],
            'endpointData' => [
                ['visitors' => 6, 'events' => 5],
                ['path' => '/exit', 'visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'expected' => [
                [
                    'data' => [
                        ['path' => '/exit', 'visitors' => 10, 'events' => 6, 'percentage' => 100, 'cr' => '50%'],
                    ],
                    'columns' => [
                        [
                            'name' => 'path',
                            'filter' => [
                                'name' => 'event:props:path',
                                'label' => 'Goals property is',
                            ],
                        ],
                        ['name' => 'visitors'],
                        ['name' => 'events'],
                        ['name' => 'cr'],
                    ],
                ],
            ],
        ];

        yield 'items without visitors field will be skipped' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '404',
            'property' => ['path'],
            'endpointData' => [
                ['path' => '/end/point', 'visitors' => 6, 'events' => 5],
                ['path' => '/exit', 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'expected' => [
                [
                    'data' => [
                        ['path' => '/end/point', 'visitors' => 6, 'events' => 5, 'percentage' => 100, 'cr' => '30%'],
                    ],
                    'columns' => [
                        [
                            'name' => 'path',
                            'filter' => [
                                'name' => 'event:props:path',
                                'label' => 'Goals property is',
                            ],
                        ],
                        ['name' => 'visitors'],
                        ['name' => 'events'],
                        ['name' => 'cr'],
                    ],
                ],
            ],
        ];

        yield 'items without events field will be skipped' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [],
            'goal' => '404',
            'property' => ['path'],
            'endpointData' => [
                ['path' => '/end/point', 'visitors' => 6],
                ['path' => '/exit', 'visitors' => 10, 'events' => 6],
            ],
            'totalVisitorData' => ['visitors' => ['value' => 20]],
            'expected' => [
                [
                    'data' => [
                        ['path' => '/exit', 'visitors' => 10, 'events' => 6, 'percentage' => 100, 'cr' => '50%'],
                    ],
                    'columns' => [
                        [
                            'name' => 'path',
                            'filter' => [
                                'name' => 'event:props:path',
                                'label' => 'Goals property is',
                            ],
                        ],
                        ['name' => 'visitors'],
                        ['name' => 'events'],
                        ['name' => 'cr'],
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getGoalPropertiesDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::getGoalPropertiesData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::calcConversionRate
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::dataCleanUp
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::roundAdaptivePrecision
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
 */
    public function getGoalPropertiesDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        string $goal,
        array $property,
        ?array $endpointData,
        ?array $totalVisitorData,
        array $expected): void
    {
        $this->languageServiceProphecy->getLL('filter.goalData.goalPropertyIs')->willReturn('Goals property is');

        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromArray($filters);

        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        if (count($property) > 0) {
            $plausibleServiceProphecy
                ->sendAuthorizedRequest(
                    $plausibleSiteId,
                    'api/v1/stats/breakdown?',
                    [
                        'site_id' => $plausibleSiteId,
                        'period' => $timeFrame,
                        'property' => 'event:props:' . $property[0],
                        'metrics' => 'visitors,events',
                    ]
                )
                ->willReturn($endpointData);
        }
        // for calcConversionRate
        $plausibleServiceProphecy
            ->sendAuthorizedRequest(
                $plausibleSiteId,
                '/api/v1/stats/aggregate?',
                [
                    'site_id' => $plausibleSiteId,
                    'period' => $timeFrame,
                    'metrics' => 'visitors',
                ]
            )
            ->willReturn($totalVisitorData);

        $subject = new GoalDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getGoalPropertiesData($goal, $plausibleSiteId, $timeFrame, $filterRepo, $property));
    }
}
