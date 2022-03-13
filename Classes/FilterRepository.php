<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio;

class FilterRepository implements \IteratorAggregate, \Countable
{
    public const FILTEREVENTPAGE = 'event:page';
    public const FILTERVISITENTRYPAGE = 'visit:entry_page';
    public const FILTERVISITEXITPAGE = 'visit:exit_page';
    public const FILTERVISITBROWSER = 'visit:browser';
    public const FILTERVISITBROWSERVERSION = 'visit:browser_version';
    public const FILTERVISITDEVICE = 'visit:device';
    public const FILTERVISITOS = 'visit:os';
    public const FILTERVISITOSVERSION = 'visit:os_version';
    public const FILTERVISITCOUNTRY = 'visit:country';
    public const FILTERVISITREGION = 'visit:region';
    public const FILTERVISITCITY = 'visit:city';
    public const FILTERVISITSOURCE = 'visit:source';
    public const FILTERVISITUTMMEDIUM = 'visit:utm_medium';
    public const FILTERVISITUTMSOURCE = 'visit:utm_source';
    public const FILTERVISITUTMCAMPAIGN = 'visit:utm_campaign';
    public const FILTERVISITUTMTERM = 'visit:utm_term';
    public const FILTERVISITUTMCONTENT = 'visit:utm_content';
    public const FILTEREVENTGOAL = 'event:goal';
    public const FILTEREVENTPROPS = 'event:props:.+';

    private array $permittedFilters = [
        self::FILTEREVENTPAGE,
        self::FILTERVISITENTRYPAGE,
        self::FILTERVISITEXITPAGE,
        self::FILTERVISITBROWSER,
        self::FILTERVISITBROWSERVERSION,
        self::FILTERVISITDEVICE,
        self::FILTERVISITOS,
        self::FILTERVISITOSVERSION,
        self::FILTERVISITCOUNTRY,
        self::FILTERVISITREGION,
        self::FILTERVISITCITY,
        self::FILTERVISITSOURCE,
        self::FILTERVISITUTMMEDIUM,
        self::FILTERVISITUTMSOURCE,
        self::FILTERVISITUTMCAMPAIGN,
        self::FILTERVISITUTMTERM,
        self::FILTERVISITUTMCONTENT,
        self::FILTEREVENTGOAL,
        self::FILTEREVENTPROPS,
    ];
    private array $filters = [];

    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->filters);
    }


    /**
     * Note: Empty values of filters are not allowed and will be skipped
     * Note: Each filter type may only occur once. Duplicate filters are removed.
     * Note: Checks if the filter is allowed, if not, it is skipped.
     *
     * @param Filter $filter
     * @return bool
     */
    public function addFilter(Filter $filter): bool
    {
        if ($this->checkFilter($filter)) {
            if ($filter->getValue() !== '') {
                // Each filter type may only occur once
                if ($this->isFilterActivated($filter->getName())) {
                    $this->removeFilter($filter->getName());
                }

                $this->filters[] = $filter;

                return true;
            }
        }

        return false;
    }

    public function getFilter(string $filterName): ?Filter
    {
        foreach ($this->filters as $filter) {
            if ($filter->getName() === $filterName) {
                return $filter;
            }
        }

        return null;
    }

    public function count(): int
    {
        return count($this->filters);
    }

    public function empty(): bool
    {
        return $this->count() === 0;
    }

    /**
     * If the filter is found with the $filterName, then its value is returned,
     * otherwise an empty string is returned. Because filters are not allowed
     * to have empty strings as values, the return value is distinct.
     *
     * @param string $filterName
     * @return string
     */
    public function getFilterValue(string $filterName): string
    {
        $filter = $this->getFilter($filterName);
        return $filter ? $filter->getValue() : '';
    }

    /**
     * Checks if the filter is an allowed filter
     * see: $this->permittedFilters
     *
     * @param Filter $filter
     * @return bool Array of Filter. All authorised filters
     */
    public function checkFilter(Filter $filter): bool
    {
        // Regular expressions are necessary here because the third part of event:props:xxx
        // can be any custom property.
        $regexpPattern = [];
        foreach ($this->permittedFilters as $pattern) {
            $regexpPattern[] = "(^" . $pattern . "$)";
        }
        $permittedFiltersRegexpPattern = implode("|", $regexpPattern);

        if (preg_match('/' . $permittedFiltersRegexpPattern . '/miu', $filter->getName())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * @param string ...$filterNames If more than one filter name is passed, true is
     *                               returned if one of the filters is active. An empty
     *                               name is not allowed.
     * @throws \InvalidArgumentException if one of the $filterNames is empty
     * @return bool
     * */
    public function isFilterActivated(string ...$filterNames): bool
    {
        foreach ($filterNames as $filterName) {
            if ($filterName === '') {
                throw new \InvalidArgumentException('To check for presence of the filter, the name must not be empty', 1556447631);
            }

            foreach ($this->filters as $filter) {
                if (preg_match('/^' . $filterName . '$/iu', $filter->getName())) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Removes all Filters from ...$filtersToRemove from the FilterRepository
     *
     * @param string ...$filtersToRemove Filter name to remove from the FilterRepository
     * @return FilterRepository This FilterRepository (not a new one)
     */
    public function removeFilter(string ...$filtersToRemove): FilterRepository
    {
        foreach ($filtersToRemove as $filterToRemove) {
            foreach ($this->filters as $index => $filter) {
                if (preg_match('/^' . $filterToRemove . '$/iu', $filter->getName())) {
                    unset($this->filters[$index]);
                    break;
                }
            }
        }

        return $this;
    }

    /**
     * @return FilterRepository This FilterRepository (not a new one)
     */
    public function clearFilters(): FilterRepository
    {
        $this->filters = [];
        return $this;
    }

    /**
     * Note: Empty names of filters are not allowed and will be skipped
     * Note: Empty values of filters are not allowed and will be skipped
     * Note: A filter for a custom property must not be active without a
     *       filter for a goal. As long as this restriction has not been
     *       remedied in Plausible, a stand-alone custom property filter
     *       is removed at this point as a precaution.
     *       See: https://plausible.io/docs/stats-api#custom-props
     *
     * @TODO The removal of a stand-alone custom property filter must be
     *       removed again as soon as Plausible allows such a filter.
     * @param array $filters
     * @return FilterRepository This FilterRepository (not a new one)
     */
    public function setFiltersFromArray(array $filters): FilterRepository
    {
        foreach ($filters as $filter) {
            if (is_array($filter)) {
                if (array_key_exists('name', $filter) && array_key_exists('value', $filter) &&
                    $filter['name'] !== '' && $filter['value'] !== '') {
                    $this->addFilter(new Filter(
                        $filter['name'],
                        $filter['value'],
                        $filter['label'] ?? '',
                        $filter['labelValue'] ?? ''
                    ));
                }
            }
        }

        if ($this->isFilterActivated(FilterRepository::FILTEREVENTPROPS) &&
            !$this->isFilterActivated(FilterRepository::FILTEREVENTGOAL)) {
            $this->removeFilter(FilterRepository::FILTEREVENTPROPS);
        }

        return $this;
    }

    /**
     * @param FilterRepository $filters
     * @return FilterRepository This FilterRepository (not a new one)
     */
    public function setFiltersFromFilterRepository(FilterRepository $filters): FilterRepository
    {
        $this->clearFilters();
        foreach ($filters as $filter) {
            $this->addFilter($filter);
        }

        return $this;
    }

    public function getFiltersAsArray(): array
    {
        $result = [];

        foreach ($this->filters as $filter) {
            $result[] = [
                'name' => $filter->getName(),
                'value' => $filter->getValue(),
                'label' => $filter->getLabel(),
                'labelValue' => $filter->getLabelValue(),
            ];
        }

        return $result;
    }

    /**
     * @return FilterRepository A new FilterRepository with the data of this
     */
    public function getRepository(): FilterRepository
    {
        $filterRepo = new FilterRepository();
        $filterRepo->setFiltersFromFilterRepository($this);
        return $filterRepo;
    }

    /**
     *
     * @return string
     */
    public function toPlausibleFilterString(): string
    {
        $filterStr = '';

        foreach ($this->filters as $filter) {
            $filterStr = $filterStr . $filter->getName() . '==' . $filter->getValue() . ';';
        }
        // remove last ';'
        $filterStr = trim($filterStr, ';');

        return $filterStr;
    }
}
