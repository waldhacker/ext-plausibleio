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

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;
use Waldhacker\Plausibleio\Services\ConfigurationService;
use Waldhacker\Plausibleio\Services\PlausibleService;

class PlausibleServiceTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::getRandomId
     */
    public function getRandomIdReturnsRandomWithPrefix(): void
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $subject = new PlausibleService(new RequestFactory(), new Client(), $configurationServiceProphecy->reveal());
        self::assertMatchesRegularExpression('/foo-[0-9a-f]{16}/i', $subject->getRandomId('foo'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::logWarning
     */
    public function nonOkStatusCodeIsLoggedAsWarning(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(400)],
            $historyContainer
        );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => 'waldhacker.dev',
            'period' => '30d',
            'property' => 'visit:device',
            'metrics' => 'visitors',
        ];

        $subject = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());
        $subject->setLogger($loggerProphecy->reveal());

        self::assertNull($subject->sendAuthorizedRequest('waldhacker.dev', $endpoint, $params));
        self::assertCount(1, $historyContainer);
        $loggerProphecy->warning('Something went wrong while fetching plausible endpoint "api/v1/stats/breakdown?" for site "waldhacker.dev": Bad Request')->shouldBeCalled();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     */
    public function numericResponsesAreReturnedAsInteger(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], trim(file_get_contents(__DIR__ . '/Fixtures/200_stats_realtime_visitors_response.json')))],
            $historyContainer
        );

        $endpoint = '/api/v1/stats/realtime/visitors?';
        $params = [
            'site_id' => 'waldhacker.dev',
        ];

        $subject = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());

        self::assertSame(42, $subject->sendAuthorizedRequest('waldhacker.dev', $endpoint, $params));
        self::assertCount(1, $historyContainer);
        self::assertSame('https://plausible.io/api/v1/stats/realtime/visitors?site_id=waldhacker.dev', (string)$historyContainer[0]['request']->getUri());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::logWarning
     */
    public function invalidJsonResponseIsLoggedAsWarning(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], trim(file_get_contents(__DIR__ . '/Fixtures/200_invalid_json_response.json')))],
            $historyContainer
        );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => 'waldhacker.dev',
            'period' => '30d',
            'property' => 'visit:device',
            'metrics' => 'visitors',
        ];

        $subject = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());
        $subject->setLogger($loggerProphecy->reveal());

        self::assertNull($subject->sendAuthorizedRequest('waldhacker.dev', $endpoint, $params));
        $loggerProphecy->warning('Something went wrong while decoding data from plausible endpoint "api/v1/stats/breakdown?" for site "waldhacker.dev": Syntax error')->shouldBeCalled();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::logWarning
     */
    public function validJsonResponseWithNoResultsIsLoggedAsWarning(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], trim(file_get_contents(__DIR__ . '/Fixtures/200_valid_json_without_results_response.json')))],
            $historyContainer
        );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => 'waldhacker.dev',
            'period' => '30d',
            'property' => 'visit:browser',
            'metrics' => 'visitors',
        ];

        $subject = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());
        $subject->setLogger($loggerProphecy->reveal());

        self::assertNull($subject->sendAuthorizedRequest('waldhacker.dev', $endpoint, $params));
        $loggerProphecy->warning('Something went wrong while fetching plausible endpoint "api/v1/stats/breakdown?" for site "waldhacker.dev"')->shouldBeCalled();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     */
    public function validJsonResponseReturnsApiDataAsArray(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], trim(file_get_contents(__DIR__ . '/Fixtures/200_browser_breakdown_response.json')))],
            $historyContainer
        );

        $loggerProphecy = $this->prophesize(LoggerInterface::class);

        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => 'waldhacker.dev',
            'period' => '30d',
            'property' => 'visit:browser',
            'metrics' => 'visitors',
        ];

        $subject = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());
        $subject->setLogger($loggerProphecy->reveal());

        self::assertSame(
            [
                [
                    'browser' => 'Chrome',
                    'visitors' => 899,
                ],
                [
                    'browser' => 'Firefox',
                    'visitors' => 263,
                ]
            ],
            $subject->sendAuthorizedRequest('waldhacker.dev', $endpoint, $params)
        );
        $loggerProphecy->warning(Argument::cetera())->shouldNotBeCalled();
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::recordEvent
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::logWarning
     */
    public function invalidParametersOnRecordEventIsLoggedAsWarning(): void
    {
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $serverRequestProphecy = $this->prophesize(ServerRequest::class);
        $normalizedParamsProphecy = $this->prophesize(NormalizedParams::class);
        $normalizedParamsProphecy->getRequestUrl()->willReturn('');
        $serverRequestProphecy->getAttribute('normalizedParams')->willReturn($normalizedParamsProphecy->reveal());
        $serverRequestProphecy->getServerParams()->willReturn(
            [
                'HTTP_REFERER' => '',
                'HTTP_USER_AGENT' => '',
                'HTTP_X_FORWARDED_FOR' => '',
            ]
        );

        $subject = new PlausibleService(new RequestFactory(), new Client(), $this->setupConfigurationServiceProphecy('waldhacker.dev')->reveal());
        $subject->setLogger($loggerProphecy->reveal());

        // no plausibleSiteId given
        self::assertFalse($subject->recordEvent('', 'https://plausible.io/', '404', $serverRequestProphecy->reveal()));
        $loggerProphecy->warning('Plausible site id can\'t be blank on recording event at endpoint "api/event"')->shouldBeCalled();
        // no Plausible API base given
        self::assertFalse($subject->recordEvent('waldhacker.dev', '', '404', $serverRequestProphecy->reveal()));
        $loggerProphecy->warning('Plausible API base url can\'t be blank on recording event at endpoint "api/event" for site "waldhacker.dev"')->shouldBeCalled();
        // no $pageUrl given -> comes from $request->getAttribute('normalizedParams')->getRequestUrl()
        self::assertFalse($subject->recordEvent('waldhacker.dev', 'https://plausible.io/', '404', $serverRequestProphecy->reveal()));
        $loggerProphecy->warning('Plausible page url can\'t be blank on recording event at endpoint "api/event" for site "waldhacker.dev"')->shouldBeCalled();
        // no eventName given
        $normalizedParamsProphecy->getRequestUrl()->willReturn('/no/site/');
        self::assertFalse($subject->recordEvent('waldhacker.dev', 'https://plausible.io/', '', $serverRequestProphecy->reveal()));
        $loggerProphecy->warning('Plausible event name can\'t be blank on recording event at endpoint "api/event" for site "waldhacker.dev"')->shouldBeCalled();
        // invalid custom property given -> only scalar values allowed as array items
        self::assertFalse($subject->recordEvent(
            'waldhacker.dev',
            'https://plausible.io/',
            '404',
            $serverRequestProphecy->reveal(),
            ['method' => 'http', 'countries' => ['AT', 'AF']])
        );
        $loggerProphecy->warning('Plausible custom properties only accepts scalar values on recording event at endpoint "api/event" for site "waldhacker.dev". The key of the faulty data is: "countries"')->shouldBeCalled();
    }

    private function createClientWithHistory(array $responses, array &$historyContainer): Client
    {
        $handlerStack = HandlerStack::create(
            new MockHandler(
                [
                    ...$responses,
                ]
            )
        );
        $history = Middleware::history($historyContainer);
        $handlerStack->push($history);
        return new Client(['handler' => $handlerStack]);
    }

    private function setupConfigurationServiceProphecy(string $plausibleSiteId): \Prophecy\Prophecy\ObjectProphecy
    {
        $configurationServiceProphecy = $this->prophesize(ConfigurationService::class);
        $configurationServiceProphecy->getApiBaseUrl($plausibleSiteId)->willReturn('https://plausible.io/');
        $configurationServiceProphecy->getApiKey($plausibleSiteId)->willReturn('super-secret-key');
        return $configurationServiceProphecy;
    }
}
