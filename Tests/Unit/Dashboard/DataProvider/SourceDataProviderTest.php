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
use Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider;
use Waldhacker\Plausibleio\Services\PlausibleService;

class SourceDataProviderTest extends UnitTestCase
{
    use ProphecyTrait;

    public static function getAllSourcesDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 3],
                ['source' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => 'source2', 'visitors' => 4],
            ],
        ];

        yield 'items without source are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 3],
                ['source' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => '', 'visitors' => 4],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['source' => 'source1', 'visitors' => 3],
                ['source' => 'source2', 'visitors' => null],
                ['source' => 'source2'],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getAllSourcesDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getAllSourcesData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     */
    public function getAllSourcesDataReturnsProperValues(
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
                'property' => 'visit:source',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getAllSourcesData($plausibleSiteId, $timeFrame));
    }

    public static function getMediumDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 3],
                ['utm_medium' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => 'source2', 'visitors' => 4],
            ],
        ];

        yield 'items without utm_medium are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 3],
                ['utm_medium' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => '', 'visitors' => 4],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_medium' => 'source1', 'visitors' => 3],
                ['utm_medium' => 'source2', 'visitors' => null],
                ['utm_medium' => 'source2'],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getMediumDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getMediumData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     */
    public function getMediumDataReturnsProperValues(
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
                'property' => 'visit:utm_medium',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getMediumData($plausibleSiteId, $timeFrame));
    }

    public static function getSourceDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 3],
                ['utm_source' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => 'source2', 'visitors' => 4],
            ],
        ];

        yield 'items without utm_source are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 3],
                ['utm_source' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => '', 'visitors' => 4],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_source' => 'source1', 'visitors' => 3],
                ['utm_source' => 'source2', 'visitors' => null],
                ['utm_source' => 'source2'],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getSourceDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getSourceData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     */
    public function getSourceDataReturnsProperValues(
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
                'property' => 'visit:utm_source',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getSourceData($plausibleSiteId, $timeFrame));
    }

    public static function getCampaignDataReturnsProperValuesDataProvider(): \Generator
    {
        yield 'all items are transformed' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 3],
                ['utm_campaign' => 'source2', 'visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => 'source2', 'visitors' => 4],
            ],
        ];

        yield 'items without utm_campaign are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 3],
                ['utm_campaign' => '', 'visitors' => 4],
                ['visitors' => 4],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
                ['label' => '', 'visitors' => 4],
            ],
        ];

        yield 'items without visitors are ignored' => [
            'plausibleSiteId' => 'waldhacker.dev',
            'timeFrame' => '7d',
            'endpointData' => [
                ['utm_campaign' => 'source1', 'visitors' => 3],
                ['utm_campaign' => 'source2', 'visitors' => null],
                ['utm_campaign' => 'source2'],
            ],
            'expected' => [
                ['label' => 'source1', 'visitors' => 3],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getCampaignDataReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::__construct
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getCampaignData
     * @covers \Waldhacker\Plausibleio\Dashboard\DataProvider\SourceDataProvider::getData
     */
    public function getCampaignDataReturnsProperValues(
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
                'property' => 'visit:utm_campaign',
                'metrics' => 'visitors',
            ]
        )
        ->willReturn($endpointData)
        ->shouldBeCalled();

        $subject = new SourceDataProvider($plausibleServiceProphecy->reveal());
        self::assertSame($expected, $subject->getCampaignData($plausibleSiteId, $timeFrame));
    }
}
