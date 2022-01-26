<?php

declare(strict_types = 1);

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use Waldhacker\Plausibleio\Services\PlausibleService;


abstract class AbstractDataProvider
{
    protected const EXT_KEY = 'plausibleio';

    protected PlausibleService $plausibleService;


    public function __construct(PlausibleService $plausibleService)
    {
        $this->plausibleService = $plausibleService;
    }

    /**
     * Checks whether all $mandatoryFields in the subarrays of $dataArray are
     * present. The mandatory field must be set but can be empty if $strict is false.
     * If this is not the case, the subarray is not included in the return value.
     *
     * @param array $mandatoryFields Array of strings. e.g. ['name', 'location']
     * @param array $dataArray Array of arrays. e.g. [['name' => 'berlin', 'location' => 'de'], ['name' => 'rome', 'location' => 'it'], ['name' => 'paris']]
     *                         The result of this example will be: [['name' => 'berlin', 'location' => 'de'], ['name' => 'rome', 'location' => 'it']]
     * @param bool $strict If $strict is true the mandatory fields must be set and
     *                     must not be empty.
     * @return array
     */
    public function dataCleanUp(array $mandatoryFields, array $dataArray, bool $strict = false): array
    {
        $result = [];

        foreach ($dataArray as $item) {
            $takeOver = true;

            foreach ($mandatoryFields as $mf) {
                if (!isset($item[$mf]) || ($strict && empty($item[$mf]))) {
                    $takeOver = false;
                    break;
                }
            }

            if ($takeOver) {
                $result[] = $item;
            }
        }

        return $result;
    }

    public function calcPercentage(array $dataArray): array
    {
        $visitorsSum = 0;

        foreach ($dataArray as $item) {
            $visitorsSum = $visitorsSum + $item['visitors'];
        }
        foreach ($dataArray as $key => $value) {
            $dataArray[$key]['percentage'] = ($value['visitors'] / $visitorsSum) * 100;
        }

        return $dataArray;
    }

    /**
     * Round numbers greater than or equal to 1 to integers. Numbers less than
     * one are rounded to the first decimal place that is not 0. Numbers less
     * than 0.01 are rounded to 0.
     * $num   return
     * 7.44   7.0
     * 1.8    2.0
     * 0.227  0.2
     * 0.078  0.07
     * 0.004  0.0
     *
     * @param float $num
     * @return float
     */
    public function roundAdaptivePrecision(float $num): float
    {
        $precision = 0;

        if ($num >= 0.01) {
            if ($num < 1) {
                $precision = 1;
            }
            if ($num < 0.1) {
                $precision = 2;
            }
        }

        return round($num, $precision);
    }

    /**
     * Calculates the Conversion Rate against the value of all unique visitors
     *
     * @param string $plausibleSiteId
     * @param string $timeFrame
     * @param array $dataArray array of arrays
     * @return array
     */
    public function calcConversionRate(string $plausibleSiteId, string $timeFrame, array $dataArray): array
    {
        $endpoint = '/api/v1/stats/aggregate?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'metrics' => 'visitors',
        ];

        $totalVisitor = 1;
        $responseData = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (
            is_array($responseData)
            && isset($responseData['visitors']['value'])
        ) {
            $totalVisitor = $responseData['visitors']['value'];
        }

        foreach ($dataArray as $id => $item) {
            $cr = ($item['visitors'] / $totalVisitor) * 100;
            $dataArray[$id]['cr'] = $this->roundAdaptivePrecision($cr);
            $dataArray[$id]['cr'] .= '%';
        }

        return $dataArray;
    }

    /**
     * Calculates the Conversion Rate on the basis of two data sets.
     * For example, if the total number of visitors ($dataWithoutGoal) with
     * the FireFox browser is 48, and the number of visitors with the FireFox
     * browser who have converted the Goal ($dataWithGoal) is 12, then the
     * conversion rate is 0.25.
     *
     * @param string $dataName Name of the data field (e.g. 'Browser' or 'OS')
     * @param array $dataWithoutGoal array of arrays. Endpoint data without goal filter
     * @param array $dataWithGoal array of arrays. Same endpoint data but with goal filter
     * @throws MissingArrayPathException If the filter does not have all the required fields
     * @return array $dataWithGoal with Conversion Rate added to each item in the array
     */
    public function calcConversionRateOnData(string $dataName, array $dataWithoutGoal, array $dataWithGoal): array
    {
        $result = [];

        foreach ($dataWithGoal as $itemWithGoal) {
            foreach ($dataWithoutGoal as $itemWithoutGoal) {
                if (isset($itemWithGoal[$dataName]) && isset($itemWithoutGoal[$dataName])) {
                    if ($itemWithGoal[$dataName] === $itemWithoutGoal[$dataName]) {
                        if (!isset($itemWithGoal['visitors']) || !isset($itemWithoutGoal['visitors'])) {
                            throw new MissingArrayPathException('Invalid data for conversion calculation.  Field \'visitors\' does not exist.', 9505005);
                        }

                        if ($itemWithoutGoal['visitors'] > 0) {
                            $itemWithGoal['cr'] = ($itemWithGoal['visitors'] / $itemWithoutGoal['visitors']) * 100;
                        } else {
                            $itemWithGoal['cr'] = 0;
                        }
                        $itemWithGoal['cr'] = $this->roundAdaptivePrecision($itemWithGoal['cr']);
                        $itemWithGoal['cr'] .= '%';

                        $result[] = $itemWithGoal;
                        break;
                    }
                } else {
                    throw new MissingArrayPathException('Invalid data for conversion calculation. Name ' . $dataName . ' does not exist.', 9505006);
                }
            }
        }

        return $result;
    }
}
