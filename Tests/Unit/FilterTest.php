<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Tests\Unit;

use _PHPStan_76800bfb5\Nette\InvalidArgumentException;
use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use Waldhacker\Plausibleio\Filter;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;


class FilterTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     */
    public function constructorThrowsExceptionIfNoNameIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $subject = new Filter('', 'value');
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\Filter::getValue
     * @covers \Waldhacker\Plausibleio\Filter::getLabel
     * @covers \Waldhacker\Plausibleio\Filter::getLabelValue
     */
    public function constructorSetParameterCorrect(): void
    {
        // test also conversion to lower case of the filter name
        $subject = new Filter('visit:GOAL', 'value');
        $this->assertSame('visit:goal', $subject->getName());
        $this->assertSame('value', $subject->getValue());

        $subject = new Filter('visit:goal', 'value', 'labelText');
        $this->assertSame('visit:goal', $subject->getName());
        $this->assertSame('value', $subject->getValue());
        $this->assertSame('labelText', $subject->getLabel());

        $subject = new Filter('visit:goal', 'value', 'labelText', 'labelValueText');
        $this->assertSame('visit:goal', $subject->getName());
        $this->assertSame('value', $subject->getValue());
        $this->assertSame('labelText', $subject->getLabel());
        $this->assertSame('labelValueText', $subject->getLabelValue());
    }
}
