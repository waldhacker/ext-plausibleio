<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Waldhacker\Plausibleio\Dashboard\Widget;

use TYPO3\CMS\Dashboard\Widgets\WidgetConfigurationInterface;
use TYPO3\CMS\Dashboard\Widgets\WidgetInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;
use Waldhacker\Plausibleio\Dashboard\DataProvider\BrowserDataProvider;

class BrowserDataWidget implements WidgetInterface
{
    private WidgetConfigurationInterface $configuration;
    private StandaloneView $view;
    private array $options;

    private BrowserDataProvider $dataProvider;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        BrowserDataProvider $dataProvider,
        StandaloneView $view,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->view = $view;
        $this->options = $options;
        $this->dataProvider = $dataProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('BrowserData');
        $items = $this->getItems();
        $sum = 0;
        foreach ($items as $item) {
            $sum += $item->visitors;
        }
        $this->view->assignMultiple([
            'items' => $items,
            'sum' => $sum,
            'options' => $this->options,
            'configuration' => $this->configuration,
            'label' => 'plausible.browserdata.label'
        ]);
        return $this->view->render();
    }

    protected function getItems(): array
    {
        return $this->dataProvider->getBrowserData(
            $this->options['timeFrame'] ?? null,
            $this->options['siteId'] ?? null
        );
    }
}
