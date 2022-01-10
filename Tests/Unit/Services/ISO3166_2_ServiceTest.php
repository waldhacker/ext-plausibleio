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

namespace Waldhacker\Plausibleio\Tests\Unit\Services;

use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\ISO3166_2_Service;

class ISO3166_2_ServiceTest extends UnitTestCase
{
    public function regionReturnsProperValuesDataProvider(): \Generator
    {
        yield 'existing isocode returns related dataset' => [
            'input' => 'AF-PAR', 'expected' => ['name' => 'Parwan', 'code' => 'AF-PAR']
        ];

        yield 'non existing alpha2 returns null' => [
            'input' => '-', 'expected' => null
        ];
    }

    /**
     * @test
     * @dataProvider regionReturnsProperValuesDataProvider
     * @covers \Waldhacker\Plausibleio\Services\ISO3166_2_Service::region
     */
    public function regionReturnsProperValues(string $input, ?array $expected): void
    {
        $subject = new ISO3166_2_Service();
        self::assertSame($expected, $subject->region($input));
    }
}
