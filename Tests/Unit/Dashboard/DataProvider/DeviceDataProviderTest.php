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
use Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class DeviceDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    private ObjectProphecy $languageServiceProphecy;

    protected function setUp(): void
    {
        parent::setUp();

        $this->languageServiceProphecy = $this->prophesize(LanguageService::class);
        $GLOBALS['LANG'] = $this->languageServiceProphecy->reveal();
    }

    public function getBrowserDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 12],
                ['browser' => 'Chrome', 'visitors' => 8],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 12, 'percentage' => 60.0],
                    ['browser' => 'Chrome', 'visitors' => 8, 'percentage' => 40.0],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without browser are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 8],
                ['browser' => '', 'visitors' => 12],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 8, 'percentage' => 40.0],
                    ['browser' => '', 'visitors' => 12, 'percentage' => 60.0],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 99],
                ['browser' => 'Chrome', 'visitors' => null],
                ['browser' => 'Chrome'],
            ],
            'expected' => [
                'data' => [
                    ['browser' => 'Firefox', 'visitors' => 99, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'browser',
                        'label' => 'Browser'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getBrowserDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getBrowserData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     */
    public function getBrowserDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.browser')->willReturn('Browser');

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/breakdown?',
            [
                'site_id' => $plausibleSiteId,
                'period' => $timeFrame,
                'property' => 'visit:browser',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getBrowserData($plausibleSiteId, $timeFrame));
    }

    public function getOSDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 32],
                ['os' => 'Linux', 'visitors' => 48],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 32, 'percentage' => 40.0],
                    ['os' => 'Linux', 'visitors' => 48, 'percentage' => 60.0],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without os are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 5],
                ['os' => '', 'visitors' => 15],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 5, 'percentage' => 25.0],
                    ['os' => '', 'visitors' => 15, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 3],
                ['os' => 'Linux', 'visitors' => null],
                ['os' => 'Linux'],
            ],
            'expected' => [
                'data' => [
                    ['os' => 'Windows', 'visitors' => 3, 'percentage' => 100],
                ],
                'columns' => [
                    [
                        'name' => 'os',
                        'label' => 'Operating system'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getOSDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getOSData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::calcPercentage
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     */
    public function getOSDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.os')->willReturn('Operating system');

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/breakdown?',
            [
                'site_id' => $plausibleSiteId,
                'period' => $timeFrame,
                'property' => 'visit:os',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getOSData($plausibleSiteId, $timeFrame));
    }

    public function getDeviceDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => 9],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 3, 'percentage' => 25.0],
                    ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without device are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 9],
                ['device' => '', 'visitors' => 3],
                ['visitors' => 4],
            ],
            'expected' => [
                'data' => [
                    ['device' => 'Tablet', 'visitors' => 9, 'percentage' => 75.0],
                    ['device' => '', 'visitors' => 3, 'percentage' => 25.0],
                ],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 3],
                ['device' => 'Desktop', 'visitors' => null],
                ['device' => 'Desktop'],
            ],
            'expected' => [
                'data' => [['device' => 'Tablet', 'visitors' => 3, 'percentage' => 100]],
                'columns' => [
                    [
                        'name' => 'device',
                        'label' => 'Screen Size'
                    ],
                    [
                        'name' => 'visitors',
                        'label' => 'Visitors'
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getDeviceDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getDeviceData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getLanguageService
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::calcPercentage
     */
    public function getDeviceDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

        $this->languageServiceProphecy->getLL('barChart.labels.visitors')->willReturn('Visitors');
        $this->languageServiceProphecy->getLL('barChart.labels.screenSize')->willReturn('Screen Size');

        $plausibleServiceProphecy->sendAuthorizedRequest(
            $plausibleSiteId,
            'api/v1/stats/breakdown?',
            [
                'site_id' => $plausibleSiteId,
                'period' => $timeFrame,
                'property' => 'visit:device',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getDeviceData($plausibleSiteId, $timeFrame));
    }

    /**
     * @test
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers       \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::calcPercentage
     */
    public function calcPercentageReturnsProperValue()
    {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);
        $subject = new DeviceDataProvider($plausibleServiceProphecy->reveal());

        self::assertSame(
            [
                ['device' => 'Tablet', 'visitors' => 3, 'percentage' => 25.0],
                ['device' => 'Desktop', 'visitors' => 9, 'percentage' => 75.0],
            ],
            $subject->calcPercentage([
                ['device' => 'Tablet', 'visitors' => 3,],
                ['device' => 'Desktop', 'visitors' => 9,],
            ])
        );
    }
}
