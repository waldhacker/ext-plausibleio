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
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider;
use Waldhacker\Plausibleio\Services\ConfigurationService;
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

    public function getDeviceDataReturnsProperValuesDataProvider(): \Generator
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
/*
        yield 'all items are transformed with filter' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'filters' => [
                ['name' => 'visit:device==Desktop'],
            ],
            'endpointData' => [
                ['device' => 'Desktop', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => 9],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Desktop', 'visitors' => 3, 'percentage' => 25.0],
                    ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size',
                        'filter' => [
                            'name' => 'visit:device',
                            'label' => 'Screen size is',
                        ],
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors',
                    ],
                ],
            ],
        ];
*/
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
     * @dataProvider getDeviceDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::getGoalsData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\GoalDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::calcPercentage
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::calcConversionRate
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::dataCleanUp
     */
    public function getGoalsDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        array $filters,
        ?array $endpointData,
        ?array $totalVisitorData,
        array $expected
    ): void {
        $requestFactoryInterfaceProphecy = $this->prophesize(RequestFactoryInterface::class);
        $clientInterfaceProphecy = $this->prophesize(ClientInterface::class);
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.goal')->willReturn('Goals');
        $this->languageServiceProphecy->getLL('filter.goalData.goalIs')->willReturn('Completed goal is');
        $this->languageServiceProphecy->getLL('barChart.labels.uniques')->willReturn('Uniques');
        $this->languageServiceProphecy->getLL('barChart.labels.total')->willReturn('Total');
        $this->languageServiceProphecy->getLL('barChart.labels.cr')->willReturn('CR');

        $plausibleServiceMock = $this->getMockBuilder(PlausibleService::class)
            ->onlyMethods(['sendAuthorizedRequest'])
            ->setConstructorArgs([
                $requestFactoryInterfaceProphecy->reveal(),
                $clientInterfaceProphecy->reveal(),
                $configurationServiceProphecy->reveal(),
            ])
            ->getMock();

        $plausibleServiceMock->expects($this->exactly(2))
            ->method('sendAuthorizedRequest')
            ->withConsecutive(
                [
                    $plausibleSiteId,
                    'api/v1/stats/breakdown?',
                    [
                        'site_id' => $plausibleSiteId,
                        'period' => $timeFrame,
                        'property' => 'event:goal',
                        'metrics' => 'visitors,events',
                    ],
                ],
                [
                    $plausibleSiteId,
                    '/api/v1/stats/aggregate?',
                    [
                        'site_id' => $plausibleSiteId,
                        'period' => $timeFrame,
                        'metrics' => 'visitors',
                    ]
                ],
            )
            ->willReturnOnConsecutiveCalls($endpointData, $totalVisitorData);

        $subject = new GoalDataProvider($plausibleServiceMock);
        self::assertSame($expected, $subject->getGoalsData($plausibleSiteId, $timeFrame /*, $filters*/));
    }
}
