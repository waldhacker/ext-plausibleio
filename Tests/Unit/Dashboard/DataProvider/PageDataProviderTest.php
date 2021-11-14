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
use Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PageDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    public function getTopPageDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['page' => '/de', 'visitors' => 3],
                ['page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
                ['label' => '/en',  'visitors' => 4],
            ],
        ];

        yield 'items without page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['page' => '/de', 'visitors' => 3],
                ['page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['page' => '/de', 'visitors' => 3],
                ['page' => '/en', 'visitors' => null],
                ['page' => '/en'],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getTopPageDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getTopPageData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     */
    public function getTopPageDataReturnsProperValues(
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
                'property' => 'event:page',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getTopPageData($plausibleSiteId, $timeFrame));
    }

    public function getEntryPageDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 3],
                ['entry_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
                ['label' => '/en',  'visitors' => 4],
            ],
        ];

        yield 'items without entry_page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 3],
                ['entry_page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['entry_page' => '/de', 'visitors' => 3],
                ['entry_page' => '/en', 'visitors' => null],
                ['entry_page' => '/en'],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getEntryPageDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getEntryPageData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     */
    public function getEntryPageDataReturnsProperValues(
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
                'property' => 'visit:entry_page',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getEntryPageData($plausibleSiteId, $timeFrame));
    }

    public function getExitPageDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 3],
                ['exit_page' => '/en', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
                ['label' => '/en',  'visitors' => 4],
            ],
        ];

        yield 'items without exit_page are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 3],
                ['exit_page' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['exit_page' => '/de', 'visitors' => 3],
                ['exit_page' => '/en', 'visitors' => null],
                ['exit_page' => '/en'],
            ],
            'expected' => [
                ['label' => '/de',  'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getExitPageDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getExitPageData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\PageDataProvider::getData
     */
    public function getExitPageDataReturnsProperValues(
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
                'property' => 'visit:exit_page',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new PageDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getExitPageData($plausibleSiteId, $timeFrame));
    }
}
