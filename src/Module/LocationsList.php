<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
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
use Contao\RequestToken;
use Contao\StringUtil;
use Contao\System;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemCategory;

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
                throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noMapsFound']);
            }

            $this->objMap = $this->maps->first();

            // Build the config (do not manage pagination here !)
            $this->arrConfig = ['published' => 1, 'where' => [
                sprintf('%s.pid in (%s)', MapItem::getTable(), implode(',', StringUtil::deserialize($this->wem_geodata_maps))),
            ]];

            // Catch AJAX request
            if (Input::post('TL_AJAX')) {
                if ($this->id === Input::post('module')) {
                    $this->handleAjaxRequests(Input::post('action'));
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
            Util::getCountries();

            // Get the jumpTo page
            // $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Gather filters
            $this->Template->filters = $this->buildFilters();
            $this->Template->filters_position = $this->wem_geodata_filters;
            $this->Template->filters_action = Environment::get('request');
            $this->Template->filters_method = 'GET';

            // pagination
            $this->numberOfItems = $this->countItems();
            if (0 === $this->numberOfItems) {
                throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
            }
            $this->buildPagination($this->numberOfItems);

            // Get locations
            // $this->arrConfig['limit'] = $this->perPage;
            $limit = $this->perPage;
            // $this->arrConfig['offset'] = $this->perPage * ((Input::get('page_n'.$this->id) ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            $offset = $this->perPage * ((Input::get('page_n'.$this->id) ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            // $arrLocations = $this->getLocations($this->arrConfig);
            $arrLocations = $this->fetchItems(null, ($limit ?: 0), $offset);

            $this->Template->locations = $arrLocations;

            // Get categories
            $arrCategories = $this->getCategories();

            $this->Template->categories = $arrCategories;

            // Add the items
            // if (!empty($arrLocations)) {
            //     $this->Template->locations = $this->parseItems($arrLocations, $this->wem_geodata_customTplForGeodataItems);
            // }

            // Send the data to Map template
            $this->Template->config = $this->arrConfig;
            $this->Template->customTplForGeodataItems = !empty($this->wem_geodata_customTplForGeodataItems) ? $this->wem_geodata_customTplForGeodataItems : 'mod_wem_geodata_list_item';
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }

    /**
     * Catch Ajax Requests.
     */
    protected function handleAjaxRequests(): void
    {
        try {
            switch (Input::post('action')) {
                case 'getLocations':
                    $arrResponse = [
                        'status' => 'success',
                        'locations' => $this->getLocationsAjax(),
                    ];
                break;
                default:
                    throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['unknownAjaxRequest'], Input::post('action')));
            }
        } catch (\Exception $e) {
            $arrResponse = ['status' => 'error', 'msg' => $e->getMessage(), 'trace' => $e->getTrace()];
        }

        // Add Request Token to JSON answer and return
        $arrResponse['rt'] = RequestToken::get();
        echo json_encode($arrResponse);
        exit;
    }

    protected function buildFilters(): array
    {
        $arrFilters = [];
        if ('nofilters' !== $this->wem_geodata_filters) {
            $locations = MapItem::findItems($this->arrConfig);
            System::loadLanguageFile('tl_wem_map_item');

            if ($this->wem_geodata_search) {
                $arrFilters['search'] = [
                    // 'label' => 'Recherche :',
                    // 'placeholder' => 'Indiquez un nom ou un code postal...',
                    'label' => $GLOBALS['TL_LANG']['tl_wem_map_item']['search'][0],
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item']['search'][1],
                    'name' => 'search',
                    'type' => 'text',
                    'value' => Input::get('search') ?: '',
                ];
                if (Input::get('search')) {
                    $this->arrConfig['search'] = Input::get('search');
                }
            }

            $arrFilterFields = unserialize($this->wem_geodata_filters_fields);
            $arrCountries = Util::getCountries();
            $arrLocations = [];
            if ($locations) {
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }
            }

            foreach ($arrFilterFields as $filterField) {
                if (Input::get($filterField)) {
                    $this->arrConfig[$filterField] = Input::get($filterField);
                }
                $arrFilters[$filterField] = [
                    'label' => sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][0]),
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][1],
                    'name' => $filterField,
                    'type' => 'select',
                    'options' => [],
                ];

                foreach ($arrLocations as $location) {
                    if (!$location[$filterField]) {
                        // HOOK: add custom logic
                        if (isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'])) {
                            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                                [$arrFilters, $this->arrConfig] = static::importStatic($callback[0])->{$callback[1]}($arrFilters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                            }
                        }
                        continue;
                    }

                    if (\array_key_exists($location[$filterField], $arrFilters[$filterField]['options'])) {
                        continue;
                    }
                    $arrFilters[$filterField]['options'][$location[$filterField]] = [
                        'value' => $location[$filterField],
                        'text' => $location[$filterField],
                        'selected' => (Input::get($filterField) && (Input::get($filterField) === $location[$filterField] || Input::get($filterField) === Util::formatStringValueForFilters((string) $location[$filterField])) ? 'selected' : ''),
                    ];
                    switch ($filterField) {
                        case 'city':
                            $arrFilters[$filterField]['options'][$location[$filterField]]['value'] = $location[$filterField];
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $location[$filterField].($location['admin_lvl_2'] ? ' ('.$location['admin_lvl_2'].')' : '');
                        break;
                        case 'category':
                            $mapItemCategories = MapItemCategory::findItems(['pid' => $location['id']]);
                            if ($mapItemCategories) {
                                while ($mapItemCategories->next()) {
                                    $objCategory = Category::findByPk($mapItemCategories->category);
                                    if ($objCategory) {
                                        $arrFilters[$filterField]['options'][$objCategory->id]['text'] = $objCategory->title;
                                        $arrFilters[$filterField]['options'][$objCategory->id]['value'] = str_replace([' ', '.'], '_', mb_strtolower((string) $objCategory->title, 'UTF-8'));
                                        $arrFilters[$filterField]['options'][$objCategory->id]['selected'] =  (\array_key_exists($filterField, $this->arrConfig) && $this->arrConfig[$filterField] === Util::formatStringValueForFilters((string) $objCategory->title) ? 'selected' : '');
                                    }
                                }
                            }
                        break;
                        case 'country':
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[strtolower($location[$filterField])] ?? $location[$filterField];
                        break;
                        default:
                            // HOOK: add custom logic
                            if (isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'])) {
                                foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                                    [$arrFilters, $this->arrConfig] = static::importStatic($callback[0])->{$callback[1]}($arrFilters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                                }
                            }
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
    protected function countItems(array $c = [])
    {
        $c = !empty($c) ? $c : $this->getListConfig();

        return $this->countLocations($c);
    }

    /**
     * Fetch the matching items.
     *
     * @param array|null $c       configuration. If none provided, the default one will be used
     * @param int|null   $limit
     * @param int|null   $offset
     * @param array|null $options
     */
    protected function fetchItems(?array $c = [], $limit = 0, $offset = 0, $options = []): ?array
    {
        $c = !empty($c) ? $c : $this->getListConfig();

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

    protected function getLocationsAjax(): array
    {
        $config = $this->arrConfig;
        $arrFilterFields = unserialize($this->wem_geodata_filters_fields);
        foreach ($arrFilterFields as $filterField) {
            if (Input::get($filterField)) {
                $config[$filterField] = Input::get($filterField);
            }
        }

        return $this->fetchItems($config);
    }
}
