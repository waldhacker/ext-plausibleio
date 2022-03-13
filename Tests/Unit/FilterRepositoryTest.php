<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Tests\Unit;

use GuzzleHttp\Client;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use Waldhacker\Plausibleio\Dashboard\DataProvider\AbstractDataProvider;
use Waldhacker\Plausibleio\Filter;
use Waldhacker\Plausibleio\FilterRepository;

use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;


class FilterRepositoryTest extends UnitTestCase
{
    use ProphecyTrait;

    public function addFilterFilterAddedCorrectlyDataProvider(): \Generator
    {
        yield 'item is added' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                    'label' => '',
                    'labelValue' => '',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];

        yield 'item with empty value is ignored' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];

        yield 'item with illegal name is ignored' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                ],
                [
                    'name' => 'visit:user',
                    'value' => 'Ronny',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider addFilterFilterAddedCorrectlyDataProvider
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getValue
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
 * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     */
    public function addFilterFilterAddedCorrectly(array $filterData, array $excepted): void
    {
        $subject = new FilterRepository();
        foreach ($filterData as $fd) {
            $subject->addFilter(new Filter($fd['name'], $fd['value']));
        }
        $this->assertSame($excepted, $subject->getFiltersAsArray());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\FilterRepository::removeFilter
     */
    public function addFilterAlreadyExistingFilterIsReplaced(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/second'));
        $this->assertSame([
                [
                    'name' => FilterRepository::FILTERVISITSOURCE,
                    'value' => 'waldhacker.dev',
                    'label' => '',
                    'labelValue' => '',
                ],
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/page/second',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
            $subject->getFiltersAsArray()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFilter
     */
    public function getFilterReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));

        $this->assertEquals(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'), $subject->getFilter(FilterRepository::FILTERVISITEXITPAGE));

        // looking for non exiting entry
        $this->assertSame(null, $subject->getFilter(FilterRepository::FILTERVISITUTMTERM));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     */
    public function countReturnsProperValue(): void
    {
        $subject = new FilterRepository();

        $this->assertSame(0, $subject->count());

        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));

        $this->assertSame(3, $subject->count());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::empty
     * @covers \Waldhacker\Plausibleio\FilterRepository::count
     */
    public function emptyReturnsProperValue(): void
    {
        $subject = new FilterRepository();

        $this->assertSame(true, $subject->empty());

        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));

        $this->assertSame(false, $subject->empty());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFilterValue
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFilter
     */
    public function getFilterValueReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));

        $this->assertSame('/page/first', $subject->getFilterValue(FilterRepository::FILTERVISITEXITPAGE));

        // looking for non exiting entry
        $this->assertSame('', $subject->getFilterValue(FilterRepository::FILTERVISITUTMTERM));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     */
    public function checkFilterReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $this->assertTrue($subject->checkFilter(new Filter(FilterRepository::FILTERVISITUTMTERM, 'value')));
        // check filter type with regex
        $this->assertTrue($subject->checkFilter(new Filter('event:props:path', '/path/sublevel')));
        // check illegal filter type
        $this->assertFalse($subject->checkFilter(new Filter('visit:user', 'Gurki')));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     */
    public function isFilterActivatedReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITOSVERSION, '11.2'));
        $subject->addFilter(new Filter('event:props:path', '/path/sublevel'));

        $this->assertTrue($subject->isFilterActivated(FilterRepository::FILTERVISITEXITPAGE));
        $this->assertTrue($subject->isFilterActivated('event:props:path'));
        $this->assertTrue($subject->isFilterActivated(FilterRepository::FILTEREVENTPROPS));
        $this->assertTrue($subject->isFilterActivated(FilterRepository::FILTERVISITEXITPAGE, FilterRepository::FILTEREVENTPROPS));
        // first filter is not active
        $this->assertTrue($subject->isFilterActivated(FilterRepository::FILTERVISITCOUNTRY, FilterRepository::FILTERVISITEXITPAGE));
        // check filter that is not in the list
        $this->assertFalse($subject->isFilterActivated(FilterRepository::FILTERVISITUTMCONTENT));
        $this->assertFalse($subject->isFilterActivated(FilterRepository::FILTERVISITOS));
        $this->assertFalse($subject->isFilterActivated(FilterRepository::FILTERVISITEXITPAGE . '\n'));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     */
    public function isFilterActivatedThrowsExceptionIfNoNameIsGiven(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITOSVERSION, '10.1'));
        $this->assertTrue($subject->isFilterActivated(FilterRepository::FILTERVISITOS, ''));
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::removeFilter
     * @covers \Waldhacker\Plausibleio\Filter::getName
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     */
    public function removeFilterRemovesFilterCorrectly(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));
        $subject->addFilter(new Filter('event:props:path', '/path/sublevel'));

        $subject->removeFilter('event:props:path');
        // try to remove filter that is not in the list
        $subject->removeFilter(FilterRepository::FILTERVISITUTMTERM);
        $this->assertSame([
                [
                    'name' => FilterRepository::FILTEREVENTGOAL,
                    'value' => '404',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
            $subject->getFiltersAsArray()
        );

        $subject->addFilter(new Filter('event:props:path', '/path/sublevel'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITOSVERSION, 'UBUNTU'));

        $subject->removeFilter(FilterRepository::FILTEREVENTPROPS, FilterRepository::FILTEREVENTGOAL);
        $this->assertSame([
                [
                    'name' => FilterRepository::FILTERVISITOSVERSION,
                    'value' => 'UBUNTU',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
            $subject->getFiltersAsArray()
        );

        $subject->addFilter(new Filter(FilterRepository::FILTERVISITOSVERSION, 'UBUNTU'));
        $subject->removeFilter(FilterRepository::FILTERVISITOS);
        $this->assertSame([
            [
                'name' => FilterRepository::FILTERVISITOSVERSION,
                'value' => 'UBUNTU',
                'label' => '',
                'labelValue' => '',
            ],
        ],
            $subject->getFiltersAsArray()
        );
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     */
    public function clearFilterClearsAllFiltersCorrectly(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));
        $subject->addFilter(new Filter(FilterRepository::FILTEREVENTPAGE, '/path/sublevel'));

        $subject->clearFilters();
        $this->assertSame([], $subject->getFiltersAsArray());
    }

    public function setFiltersFromArrayFilterAddedCorrectlyDataProvider(): \Generator
    {
        yield 'item is added' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => 'labelText',
                    'labelValue' => 'labelValueText',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                    'label' => '',
                    'labelValue' => '',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => 'labelText',
                    'labelValue' => 'labelValueText',
                ],
            ],
        ];

        yield 'item with empty name is ignored' => [
            [
                [
                    'name' => '',
                    'value' => 'value',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];

        yield 'item with empty value is ignored' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '',
                ],
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTEREVENTPAGE,
                    'value' => '/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];

        yield 'item with illegal name is ignored' => [
            [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                ],
                [
                    'name' => 'visit:user',
                    'value' => 'Ronny',
                ],
            ],
            'excepted' => [
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/exit/page',
                    'label' => '',
                    'labelValue' => '',
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider setFiltersFromArrayFilterAddedCorrectlyDataProvider
     * @covers       \Waldhacker\Plausibleio\Filter::__construct
     * @covers       \Waldhacker\Plausibleio\FilterRepository::setFiltersFromArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers       \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers       \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
 */
    public function setFiltersFromArrayFilterAddedCorrectly(array $filterData, array $excepted): void
    {
        $subject = new FilterRepository();
        $subject->setFiltersFromArray($filterData);
        $this->assertSame($excepted, $subject->getFiltersAsArray());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function setFiltersFromFilterRepositoryFilterAddedCorrectly(): void
    {
        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev'));
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first'));
        $filterRepo->addFilter(new Filter(FilterRepository::FILTEREVENTGOAL, '404'));

        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/subpage'));
        $subject->setFiltersFromFilterRepository($filterRepo);

        $this->assertEquals($filterRepo, $subject);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::getFiltersAsArray
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function getFiltersAsArrayReturnsProperValue(): void
    {
        $filterRepo = new FilterRepository();
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev', 'labelText'));
        $filterRepo->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first', '', 'labelValueText'));

        $this->assertSame([
                [
                    'name' => FilterRepository::FILTERVISITSOURCE,
                    'value' => 'waldhacker.dev',
                    'label' => 'labelText',
                    'labelValue' => '',
                ],
                [
                    'name' => FilterRepository::FILTERVISITEXITPAGE,
                    'value' => '/page/first',
                    'label' => '',
                    'labelValue' => 'labelValueText',
                ],
            ],
            $filterRepo->getFiltersAsArray()
        );

        $filterRepo->clearFilters();
        $this->assertSame([], $filterRepo->getFiltersAsArray());
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::getRepository
     * @covers \Waldhacker\Plausibleio\FilterRepository::setFiltersFromFilterRepository
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::getIterator
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
 */
    public function getRepositoryReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev', 'labelText'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first', '', 'labelValueText'));

        $this->assertEquals($subject->getRepository(), $subject);
    }

    /**
     * @test
     * @covers \Waldhacker\Plausibleio\FilterRepository::toPlausibleFilterString
     * @covers \Waldhacker\Plausibleio\Filter::__construct
     * @covers \Waldhacker\Plausibleio\FilterRepository::addFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::checkFilter
     * @covers \Waldhacker\Plausibleio\FilterRepository::clearFilters
     * @covers \Waldhacker\Plausibleio\FilterRepository::isFilterActivated
     */
    public function toPlausibleFilterStringReturnsProperValue(): void
    {
        $subject = new FilterRepository();
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITSOURCE, 'waldhacker.dev', 'labelText'));
        $subject->addFilter(new Filter(FilterRepository::FILTERVISITEXITPAGE, '/page/first', '', 'labelValueText'));

        $this->assertSame(
            FilterRepository::FILTERVISITSOURCE . '==waldhacker.dev;' . FilterRepository::FILTERVISITEXITPAGE . '==/page/first',
            $subject->toPlausibleFilterString()
        );

        $subject->clearFilters();
        $this->assertSame('', $subject->toPlausibleFilterString());
    }
}
