<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\Module;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Item;
use WEM\GeoDataBundle\Model\Map;

/**
 * Front end module "locations list".
 */
class LocationsList extends Core
{
    /**
     * Map Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_geodata_list';

    /**
     * Filters.
     *
     * @var array [Available filters]
     */
    protected $filters;
    /** @var array */
    protected $arrConfig;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['wem_display_list'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        try {
            // Load the map
            $this->maps = Map::findItems([
                'where' => [
                    sprintf('id in (%s)', implode(',', StringUtil::deserialize($this->wem_geodata_maps))),
                ],
            ]);

            if (!$this->maps) {
                throw new \Exception('No maps found.');
            }

            // Catch AJAX request
            if (Input::post('TL_AJAX')) {
                if ($this->id === Input::post('module')) {
                    $this->handleAjaxRequest(Input::post('action'));
                }
            }

            $limit = null;
            $offset = (int) $this->skipFirst;

            // Maximum number of items
            if ($this->numberOfItems > 0) {
                $limit = $this->numberOfItems;
            }

            // Load the libraries
            // ClassLoader::loadLibraries($this->objMap, 1);
            System::getCountries();

            // Build the config (do not manage pagination here !)
            $this->arrConfig = ['published' => 1, 'where' => [
                sprintf('pid in (%s)', implode(',', StringUtil::deserialize($this->wem_geodata_maps))),
            ]];

            // Get the jumpTo page
            // $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Gather filters
            $this->Template->filters = $this->buildFilters();
            $this->Template->filters_position = $this->wem_geodata_filters;
            $this->Template->filters_action = Environment::get('request');
            $this->Template->filters_method = 'GET';

            // pagination
            $this->numberOfItems = $this->countItems();
            $this->buildPagination($this->numberOfItems);

            // Get locations
            // $this->arrConfig['limit'] = $this->perPage;
            $limit = $this->perPage;
            // $this->arrConfig['offset'] = $this->perPage * ((Input::get('page_n'.$this->id) ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            $offset = $this->perPage * ((Input::get('page_n'.$this->id) ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            // $arrLocations = $this->getLocations($this->arrConfig);
            $arrLocations = $this->fetchItems(($limit ?: 0), $offset);

            $this->Template->locations = $arrLocations;

            // Add the items
            // if (!empty($arrLocations)) {
            //     $this->Template->locations = $this->parseItems($arrLocations, $this->wem_geodata_customTplForGeodataItems);
            // }

            // Send the data to Map template
            $this->Template->config = $this->arrConfig;
            $this->Template->customTplForGeodataItems = $this->wem_geodata_customTplForGeodataItems ?? 'mod_wem_geodata_list_item';
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }

    protected function buildFilters(): array
    {
        $arrFilters = [];
        if ('nofilters' !== $this->wem_geodata_filters) {
            $locations = Item::findItems($this->arrConfig);
            System::loadLanguageFile('tl_wem_item');

            if ($this->wem_geodata_search) {
                $arrFilters['search'] = [
                    // 'label' => 'Recherche :',
                    // 'placeholder' => 'Indiquez un nom ou un code postal...',
                    'label' => $GLOBALS['TL_LANG']['tl_wem_item']['search'][0],
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_item']['search'][1],
                    'name' => 'search',
                    'type' => 'text',
                    'value' => Input::get('search') ?: '',
                ];
                if (Input::get('search')) {
                    $this->arrConfig['search'] = Input::get('search');
                }
            }

            $arrFilterFields = unserialize($this->wem_geodata_filters_fields);
            $arrCountries = System::getContainer()->get('contao.intl.countries')->getCountries();
            $arrLocations = [];
            while ($locations->next()) {
                $arrLocations[] = $locations->current()->row();
            }

            foreach ($arrFilterFields as $filterField) {
                if (Input::get($filterField)) {
                    $this->arrConfig[$filterField] = Input::get($filterField);
                }
                $arrFilters[$filterField] = [
                    'label' => sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_item'][$filterField][0]),
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_item'][$filterField][1],
                    'name' => $filterField,
                    'type' => 'select',
                    'options' => [],
                ];

                foreach ($arrLocations as $location) {
                    if (!$location[$filterField]) {
                        continue;
                    }

                    if (\array_key_exists($location[$filterField], $arrFilters[$filterField]['options'])) {
                        continue;
                    }
                    $arrFilters[$filterField]['options'][$location[$filterField]] = [
                        'value' => $location[$filterField],
                        'text' => $location[$filterField],
                        'selected' => (Input::get($filterField) && Input::get($filterField) === $location[$filterField] ? 'selected' : ''),
                    ];
                    switch ($filterField) {
                        case 'city':
                            $arrFilters[$filterField]['options'][$location[$filterField]]['value'] = $location[$filterField];
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $location[$filterField].' ('.$location['admin_lvl_2'].')';
                        break;
                        case 'category':
                            $objCategory = Category::findByPk($location[$filterField]);
                            if ($objCategory) {
                                $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $objCategory->title;
                            }
                        break;
                        case 'country':
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[strtoupper($location[$filterField])] ?? $location[$filterField];
                        break;
                    }
                }
            }
        }

        return $arrFilters;
    }

    protected function getListConfig()
    {
        return $this->arrConfig;
    }

    /**
     * Count the total matching items.
     *
     * @return int
     */
    protected function countItems()
    {
        $c = $this->getListConfig();

        return $this->countLocations($c);
    }

    /**
     * Fetch the matching items.
     *
     * @param int   $limit
     * @param int   $offset
     * @param array $options
     */
    protected function fetchItems($limit, $offset, $options = []): ?array
    {
        $c = $this->getListConfig();

        $c['limit'] = $limit;
        $c['offset'] = $offset;

        return $this->getLocations($c);
    }

    /**
     * Parse multiple items.
     *
     * @param string $strTemplate
     */
    protected function parseItems(array $objItems, ?string $strTemplate = 'mod_wem_geodata_list_item'): array
    {
        try {
            $limit = \count($objItems);
            if ($limit < 1) {
                return [];
            }

            $count = 0;
            $arrItems = [];
            foreach ($objItems as $location) {
                $arrItems[] = $this->parseItem($location, $strTemplate, ((1 === ++$count) ? ' first' : '').(($count === $limit) ? ' last' : '').((0 === ($count % 2)) ? ' odd' : ' even'), $count);
            }

            return $arrItems;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function parseItem(array $objItem, $strTemplate = 'mod_wem_geodata_list_item', $strClass = '', $intCount = 0)
    {
        try {
            /** @var FrontendTemplate $objTemplate */
            $objTemplate = new FrontendTemplate($strTemplate);
            $objTemplate->setData($objItem);
            $objTemplate->class = $strClass;
            $objTemplate->count = $intCount;

            return $objTemplate->parse();
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
