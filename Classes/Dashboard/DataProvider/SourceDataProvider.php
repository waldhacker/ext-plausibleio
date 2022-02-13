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

namespace Waldhacker\Plausibleio\Dashboard\DataProvider;

use Waldhacker\Plausibleio\FilterRepository;

class SourceDataProvider extends AbstractDataProvider
{
    public function getAllSourcesDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $allSourcesDataWithGoal = $this->getAllSourcesDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $allSourcesDataWithoutGoal = $this->getAllSourcesDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($allSourcesDataWithoutGoal['columns'][0]['name'], $allSourcesDataWithoutGoal['data'], $allSourcesDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (Source) does not have to be determined.
            $allSourcesDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getAllSourcesDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $sourceFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITSOURCE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:source', $filters);

        $map = $this->dataCleanUp(['source', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $sourceColumn = [
            'name' => 'source',
            'label' => $this->getLanguageService()->getLL('barChart.labels.source'),
        ];
        // When filtering by source there is no deeper filter than that
        if (!$sourceFilterActivated) {
            $sourceColumn['filter'] = [
                'name' => 'visit:source',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.sourceIs'),
            ];
        }
        array_unshift($responseData['columns'], $sourceColumn);

        return $responseData;
    }

    public function getAllSourcesData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getAllSourcesDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getAllSourcesDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getMediumDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $mediumDataWithGoal = $this->getMediumDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $mediumDataWithoutGoal = $this->getMediumDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($mediumDataWithoutGoal['columns'][0]['name'], $mediumDataWithoutGoal['data'], $mediumDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (UTM Medium) does not have to be determined.
            $mediumDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getMediumDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $mediumFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITUTMMEDIUM);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_medium', $filters);

        $map = $this->dataCleanUp(['utm_medium', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $mediumColumn = [
            'name' => 'utm_medium',
            'label' => $this->getLanguageService()->getLL('barChart.labels.UTMMedium'),
        ];
        // When filtering by medium there is no deeper filter than that
        if (!$mediumFilterActivated) {
            $mediumColumn['filter'] = [
                'name' => 'visit:utm_medium',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMMediumIs'),
            ];
        }
        array_unshift($responseData['columns'], $mediumColumn);

        return $responseData;
    }

    public function getMediumData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getMediumDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getMediumDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getSourceDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $sourceDataWithGoal = $this->getSourceDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $sourceDataWithoutGoal = $this->getSourceDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($sourceDataWithoutGoal['columns'][0]['name'], $sourceDataWithoutGoal['data'], $sourceDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (UTM Source) does not have to be determined.
            $sourceDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getSourceDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $sourceFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITUTMSOURCE);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_source', $filters);

        $map = $this->dataCleanUp(['utm_source', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $sourceColumn = [
            'name' => 'utm_source',
            'label' => $this->getLanguageService()->getLL('barChart.labels.UTMSource'),
        ];
        // When filtering by source there is no deeper filter than that
        if (!$sourceFilterActivated) {
            $sourceColumn['filter'] = [
                'name' => 'visit:utm_source',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMSourceIs'),
            ];
        }
        array_unshift($responseData['columns'], $sourceColumn);

        return $responseData;
    }

    public function getSourceData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getSourceDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getSourceDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getCampaignDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $campaignDataWithGoal = $this->getCampaignDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $campaignDataWithoutGoal = $this->getCampaignDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($campaignDataWithoutGoal['columns'][0]['name'], $campaignDataWithoutGoal['data'], $campaignDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (browser, version) does not have to be determined.
            $campaignDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getCampaignDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $campaignFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITUTMCAMPAIGN);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_campaign', $filters);

        $map = $this->dataCleanUp(['utm_campaign', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $campaignColumn = [
            'name' => 'utm_campaign',
            'label' => $this->getLanguageService()->getLL('barChart.labels.UTMCampaign'),
        ];
        // When filtering by campaign there is no deeper filter than that
        if (!$campaignFilterActivated) {
            $campaignColumn['filter'] = [
                'name' => 'visit:utm_campaign',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMCampaignIs'),
            ];
        }
        array_unshift($responseData['columns'], $campaignColumn);

        return $responseData;
    }

    public function getCampaignData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getCampaignDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getCampaignDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getTermDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $termDataWithGoal = $this->getTermDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $termDataWithoutGoal = $this->getTermDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($termDataWithoutGoal['columns'][0]['name'], $termDataWithoutGoal['data'], $termDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (UTM Term) does not have to be determined.
            $termDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getTermDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $termFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITUTMTERM);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_term', $filters);

        $map = $this->dataCleanUp(['utm_term', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $termColumn = [
            'name' => 'utm_term',
            'label' => $this->getLanguageService()->getLL('barChart.labels.UTMTerm'),
        ];
        // When filtering by term there is no deeper filter than that
        if (!$termFilterActivated) {
            $termColumn['filter'] = [
                'name' => 'visit:utm_term',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMTermIs'),
            ];
        }
        array_unshift($responseData['columns'], $termColumn);

        return $responseData;
    }

    public function getTermData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getTermDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getTermDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    public function getContentDataWithGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $contentDataWithGoal = $this->getContentDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        $filtersWithoutGoal = $filters->getRepository()->removeFilter(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);
        $contentDataWithoutGoal = $this->getContentDataWithoutGoal($plausibleSiteId, $timeFrame, $filtersWithoutGoal);

        $result = [];
        $result['data'] = $this->calcConversionRateOnData($contentDataWithoutGoal['columns'][0]['name'], $contentDataWithoutGoal['data'], $contentDataWithGoal['data']);
        $result['columns'] = [
            // Take over the data name column, so the correct label (UTM Content) does not have to be determined.
            $contentDataWithoutGoal['columns'][0],
            [
                'name' => 'visitors',
                'label' => $this->getLanguageService()->getLL('barChart.labels.conversions'),
            ],
            [
                'name' => 'cr',
                'label' => $this->getLanguageService()->getLL('barChart.labels.cr'),
            ],
        ];

        return $result;
    }

    public function getContentDataWithoutGoal(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $contentFilterActivated = $filters->isFilterActivated(FilterRepository::FILTERVISITUTMCONTENT);
        $responseData = $this->getData($plausibleSiteId, $timeFrame, 'visit:utm_content', $filters);

        $map = $this->dataCleanUp(['utm_content', 'visitors'], $responseData['data']);
        $map = $this->calcPercentage($map);
        $responseData['data'] = $map;

        $contentColumn = [
            'name' => 'utm_content',
            'label' => $this->getLanguageService()->getLL('barChart.labels.UTMContent'),
        ];
        // When filtering by content there is no deeper filter than that
        if (!$contentFilterActivated) {
            $contentColumn['filter'] = [
                'name' => 'visit:utm_content',
                'label' => $this->getLanguageService()->getLL('filter.sourceData.UTMContentIs'),
            ];
        }
        array_unshift($responseData['columns'], $contentColumn);

        return $responseData;
    }

    public function getContentData(string $plausibleSiteId, string $timeFrame, FilterRepository $filters): array
    {
        $goalFilterActivated = $filters->isFilterActivated(FilterRepository::FILTEREVENTGOAL, FilterRepository::FILTEREVENTPROPS);

        if (!$goalFilterActivated) {
            return $this->getContentDataWithoutGoal($plausibleSiteId, $timeFrame, $filters);
        } else {
            return $this->getContentDataWithGoal($plausibleSiteId, $timeFrame, $filters);
        }
    }

    private function getData(string $plausibleSiteId, string $timeFrame, string $property, FilterRepository $filters): array
    {
        $endpoint = 'api/v1/stats/breakdown?';
        $params = [
            'site_id' => $plausibleSiteId,
            'period' => $timeFrame,
            'property' => $property,
            'metrics' => 'visitors',
        ];
        $filterStr = $filters->toPlausibleFilterString();
        if ($filterStr) {
            $params['filters'] = $filterStr;
        }

        $responseData = [];
        $responseData['data'] = $this->plausibleService->sendAuthorizedRequest($plausibleSiteId, $endpoint, $params);
        if (!is_array($responseData['data'])) {
            $responseData['data'] = [];
        }

        $responseData['columns'][] = [
            'name' => 'visitors',
            'label' => $this->getLanguageService()->getLL('barChart.labels.visitors'),
        ];

        return $responseData;
    }
}
