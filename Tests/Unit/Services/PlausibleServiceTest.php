<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Tests\Unit\Services;


use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Log\LoggerInterface;
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
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::getVisitors
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     */
    public function getVisitorDataReturnsVisitorDataTimeLineFromAPI(): void
    {
        $set1 = new \stdClass();
        $set1->date = '2021-04-16';
        $set1->visitors = 57;

        $set2 = new \stdClass();
        $set2->date = '2021-04-17';
        $set2->visitors = 15;

        $expected = [$set1, $set2];
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/200_visitors_timeline_response.json'))],
            $historyContainer
        );

        $plausibleService = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationService()->reveal());
        $result = $plausibleService->getVisitors('30d', 'example.com');

        self::assertEquals($expected, $result);
        self::assertCount(1, $historyContainer);
        self::assertSame('https://example.comapi/v1/stats/timeseries?site_id=example.com&period=30d', (string)$historyContainer[0]['request']->getUri());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::getBrowserData()
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     */
    public function getBrowserDataReturnsBrowserDataFromAPI(): void
    {
        $set1 = new \stdClass();
        $set1->browser = 'Chrome';
        $set1->visitors = 899;

        $set2 = new \stdClass();
        $set2->browser = 'Firefox';
        $set2->visitors = 263;
        $expected = [$set1,$set2];

        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/200_browser_breakdown_response.json'))],
            $historyContainer
        );

        $plausibleService = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationService()->reveal());
        $result = $plausibleService->getBrowserData('30d', 'example.com');

        self::assertEquals($expected, $result);
        self::assertCount(1, $historyContainer);
        self::assertSame('https://example.comapi/v1/stats/breakdown?site_id=example.com&period=30d&property=visit%3Abrowser&metrics=visitors', (string)$historyContainer[0]['request']->getUri());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::getDeviceData
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     */
    public function getDeviceDataReturnsBrowserDataFromAPI(): void
    {
        $set1 = new \stdClass();
        $set1->device = 'Desktop';
        $set1->visitors = 1054;

        $set2 = new \stdClass();
        $set2->device = 'Laptop';
        $set2->visitors = 215;
        $expected = [$set1, $set2];

        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(200, [], file_get_contents(__DIR__ . '/Fixtures/200_device_breakdown_response.json'))],
            $historyContainer
        );

        $plausibleService = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationService()->reveal());
        $result = $plausibleService->getDeviceData('30d', 'example.com');

        self::assertEquals($expected, $result);
        self::assertCount(1, $historyContainer);
        self::assertSame('https://example.comapi/v1/stats/breakdown?site_id=example.com&period=30d&property=visit%3Adevice&metrics=visitors', (string)$historyContainer[0]['request']->getUri());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::__construct
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::sendAuthorizedRequest
     * @covers \Waldhacker\Plausibleio\Services\PlausibleService::getDeviceData
     */
    public function nonOkStatusCodeIsLoggedAsWarning(): void
    {
        $historyContainer = [];
        $client = $this->createClientWithHistory(
            [new Response(400)],
            $historyContainer
        );

        $plausibleService = new PlausibleService(new RequestFactory(), $client, $this->setupConfigurationService()->reveal());
        $loggerProphecy = $this->prophesize(LoggerInterface::class);
        $plausibleService->setLogger($loggerProphecy->reveal());

        $result = $plausibleService->getDeviceData('30d', 'example.com');

        self::assertSame([], $result);
        $loggerProphecy->warning('Something went wrong while fetching analytics. Bad Request')->shouldHaveBeenCalled();
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

    /**
     * @return \Prophecy\Prophecy\ObjectProphecy|ConfigurationService
     */
    private function setupConfigurationService()
    {
        $configurationService = $this->prophesize(ConfigurationService::class);
        $configurationService->getBaseUrl()->willReturn('https://example.com');
        $configurationService->getApiKey()->willReturn('super-secret-key');
        return $configurationService;
    }
}
