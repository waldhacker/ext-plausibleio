<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio;

class Filter
{
    private string $name;
    private string $value;
    private string $label;
    private string $labelValue;

    /**
     * Note: Empty names in filters are not allowed and lead to an exception
     *
     * @param string $name Name of the filter of type FilterRepository::FILTER***
     * @throws \InvalidArgumentException if the path is empty, or if the path does not exist
     */
    public function __construct(string $name, string $value, string $label = '', string $labelValue = '')
    {
        if ($name === '') {
            throw new \InvalidArgumentException('Filter cannot be constructed without a name', 1556447621);
        }

        $this->name = strtolower($name);
        $this->value = $value;
        $this->label = $label;
        $this->labelValue = $labelValue;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabel(): string
    {
        return $this->label;
    }

    /**
     * @return string
     * @codeCoverageIgnore
     */
    public function getLabelValue(): string
    {
        return $this->labelValue;
    }
}
