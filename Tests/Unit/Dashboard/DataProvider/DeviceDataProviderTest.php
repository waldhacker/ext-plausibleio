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
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class DeviceDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    public function getBrowserDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 3],
                ['browser' => 'Chrome', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Firefox',  'visitors' => 3],
                ['label' => 'Chrome',  'visitors' => 4],
            ],
        ];

        yield 'items without browser are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 3],
                ['browser' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Firefox',  'visitors' => 3],
                ['label' => '',  'visitors' => 4],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['browser' => 'Firefox', 'visitors' => 3],
                ['browser' => 'Chrome', 'visitors' => null],
                ['browser' => 'Chrome'],
            ],
            'expected' => [
                ['label' => 'Firefox',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getBrowserDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getBrowserData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     */
    public function getBrowserDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

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
                ['os' => 'Windows', 'visitors' => 3],
                ['os' => 'Linux', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Windows',  'visitors' => 3],
                ['label' => 'Linux',  'visitors' => 4],
            ],
        ];

        yield 'items without os are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['os' => 'Windows', 'visitors' => 3],
                ['os' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Windows',  'visitors' => 3],
                ['label' => '',  'visitors' => 4],
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
                ['label' => 'Windows',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getOSDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getOSData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     */
    public function getOSDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

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
                ['device' => 'Desktop', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Tablet',  'visitors' => 3],
                ['label' => 'Desktop',  'visitors' => 4],
            ],
        ];

        yield 'items without device are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['device' => 'Tablet', 'visitors' => 3],
                ['device' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'Tablet',  'visitors' => 3],
                ['label' => '',  'visitors' => 4],
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
                ['label' => 'Tablet',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getDeviceDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getDeviceData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\DeviceDataProvider::getData
     */
    public function getDeviceDataReturnsProperValues(
        string $plausibleSiteId,
        string $timeFrame,
        ?array $endpointData,
        array $expected
    ): void {
        $plausibleServiceProphecy = $this->prophesize(PlausibleService::class);

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
}
