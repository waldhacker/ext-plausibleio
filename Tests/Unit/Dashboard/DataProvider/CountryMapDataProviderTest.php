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
use Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider;
use Waldhacker\Plausibleio\Services\ISO3166Service;
use Waldhacker\Plausibleio\Services\PlausibleService;

class CountryMapDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    public function getCountryDataForDataMapReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['country' => 'DE', 'visitors' => 3],
                ['country' => 'US', 'visitors' => 4],
            ],
            'expected' => [
                ['DEU',  3],
                ['USA', 4],
            ],
        ];

        yield 'items without country are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['country' => 'DE', 'visitors' => 3],
                ['country' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['DEU',  3],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['country' => 'DE', 'visitors' => 3],
                ['country' => 'US', 'visitors' => null],
                ['country' => 'US'],
            ],
            'expected' => [
                ['DEU',  3],
            ],
        ];

        yield 'items with invalid iso code are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['country' => 'DE', 'visitors' => 3],
                ['country' => '_', 'visitors' => 4],
            ],
            'expected' => [
                ['DEU',  3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getCountryDataForDataMapReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider::getCountryDataForDataMap
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider::getCountryData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\CountryMapDataProvider::plausibleToDataMap
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::alpha2
     * @covers \Waldhacker\Plausibleio\Services\ISO3166Service::search
     */
    public function getCountryDataForDataMapReturnsProperValues(
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
                'property' => 'visit:country',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new CountryMapDataProvider($plausibleServiceProphecy->reveal(), new ISO3166Service());
        self::assertSame($expected, $subject->getCountryDataForDataMap($plausibleSiteId, $timeFrame));
    }
}
