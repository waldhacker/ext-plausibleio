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

namespace Waldhacker\Plausibleio\Services;

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class LocationCodeService
{
    private const EXT_KEY = 'plausibleio';
    private const CITYFILE = 'Resources' . \DIRECTORY_SEPARATOR . 'Private' . \DIRECTORY_SEPARATOR . 'Vendor' . \DIRECTORY_SEPARATOR . 'cities.csv';
    public const CITYNAME = 'city';
    public const LOCATIONIDNAME = 'locationID';

    //private ExtensionManagementUtility $extensionManagementUtility;
    private array $citiesData = [];

    /*
    public function __construct(
        ExtensionManagementUtility $extensionManagementUtility
    ) {
        $this->extensionManagementUtility = $extensionManagementUtility;
    }
    */

    private function binarySearch(int $locationCode, array $citiesData): ?array
    {
        // check for empty array
        if (count($citiesData) === 0) {
            return null;
        }

        $low = 0;
        $high = count($citiesData) - 1;

        while ($low <= $high) {
            // compute middle index
            $mid = floor(($low + $high) / 2);

            // element found at mid
            if ($citiesData[$mid][self::LOCATIONIDNAME] == $locationCode) {
                return $citiesData[$mid];
            }

            if ($locationCode < $citiesData[$mid][self::LOCATIONIDNAME]) {
                // search the left side of the array
                $high = $mid - 1;
            } else {
                // search the right side of the array
                $low = $mid + 1;
            }
        }

        // If we reach here element x doesn't exist
        return null;
    }

    public function codeToCityData(int $locationCode): ?array
    {
        $csvFilePathAndName = ExtensionManagementUtility::extPath(self::EXT_KEY) . self::CITYFILE;

        if (empty($this->citiesData) && file_exists($csvFilePathAndName)) {
            if (($handle = fopen($csvFilePathAndName, "r")) !== false) {
                while (($data = fgetcsv($handle, 500, "\t")) !== false) {
                    $this->citiesData[] = [self::LOCATIONIDNAME => $data[0], self::CITYNAME => $data[1]];
                }
                fclose($handle);
            }
        }

        return $this->binarySearch($locationCode, $this->citiesData);
    }
}
