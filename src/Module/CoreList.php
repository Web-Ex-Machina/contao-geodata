<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Module;

use Contao\Config;
use Contao\ContentModel;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use Exception;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemAttributeValue;
use WEM\GeoDataBundle\Model\MapItemCategory;

/**
 * Parent class for locations modules.
 */
abstract class CoreList extends Core
{
    protected function buildFilters(): void
    {
        // Gather filters
        if ($this->wem_geodata_filters !== 'nofilters') {
            $this->filters = [];
            $this->retrieveGetAttributes();
            $locations = MapItem::findItems($this->arrConfigDefault);

            if ($this->wem_geodata_search) {
                $this->filters['search'] = [
                    'label' => $GLOBALS['TL_LANG']['tl_wem_map_item']['search'][0],
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item']['search'][1],
                    'name' => 'search',
                    'type' => 'text',
                    'value' => Input::post('search') ?: '',
                ];

                if (Input::post('search')) {
                    $this->arrConfig['search'] = Input::post('search');
                }
            }

            $arrFilterFields = unserialize($this->wem_geodata_filters_fields);

            // Make sure country filter is BEFORE city filter
            if (
                in_array('country', $arrFilterFields)
                && in_array('city', $arrFilterFields)
                && array_search('city', $arrFilterFields) < array_search('country', $arrFilterFields)
            ) {
                $arrFilterFields[array_search('city', $arrFilterFields)] = 'country';
                $arrFilterFields[array_search('country', $arrFilterFields)] = 'city';
            }

            $arrLocations = [];
            if ($locations instanceof Collection) {
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }
            }

            $arrCountries = Util::getCountries();

            foreach ($arrFilterFields as $filterField) {
                if (Input::post($filterField)) {
                    $this->arrConfig[$filterField] = Input::post($filterField);
                }

                $this->filters[$filterField] = [
                    'label' => \sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][0]),
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][1],
                    'name' => $filterField,
                    'type' => 'select',
                    'options' => [],
                ];
                $arrSortOptions = [];

                foreach ($arrLocations as $location) {
                    if (!$location[$filterField]) {
                        if (isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'])) {
                            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                                [$this->filters,$this->arrConfig] = static::importStatic(
                                    $callback[0]
                                )->{$callback[1]}($this->filters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                            }
                        }

                        continue;
                    }

                    if (\array_key_exists($location[$filterField], $this->filters[$filterField]['options'])) {
                        continue;
                    }

                    if ($filterField !== 'category') {
                        $this->filters[$filterField]['options'][$location[$filterField]] = [
                            'value' => Util::formatStringValueForFilters((string) $location[$filterField]),
                            'text' => $location[$filterField],
                            'selected' => \array_key_exists(
                                $filterField,
                                $this->arrConfig
                            ) && $this->arrConfig[$filterField] === Util::formatStringValueForFilters(
                                (string) $location[$filterField]
                            ) ? 'selected' : '',
                        ];

                        $arrSortOptions[$location[$filterField]] = $location[$filterField];
                    }

                    switch ($filterField) {
                        case 'category':
                            $mapItemCategories = MapItemCategory::findItems(['pid' => $location['id']]);
                            if ($mapItemCategories instanceof Collection) {
                                while ($mapItemCategories->next()) {
                                    $objCategory = Category::findById($mapItemCategories->category);
                                    if ($objCategory) {
                                        $this->filters[$filterField]['options'][$objCategory->id]['text'] = $objCategory->title;
                                        $this->filters[$filterField]['options'][$objCategory->id]['value'] = Util::formatStringValueForFilters(
                                            (string) $objCategory->title
                                        );
                                        $this->filters[$filterField]['options'][$objCategory->id]['selected'] = (\array_key_exists(
                                            $filterField,
                                            $this->arrConfig
                                        ) && $this->arrConfig[$filterField] === Util::formatStringValueForFilters(
                                            (string) $objCategory->title
                                        ) ? 'selected' : '');

                                        $arrSortOptions[$objCategory->id] = $objCategory->title;
                                    }
                                }
                            }

                            break;
                        case 'country':
                            $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[$location[$filterField]] ?? ucfirst($location[$filterField]);
                            $arrSortOptions[$location[$filterField]] = $this->filters[$filterField]['options'][$location[$filterField]]['text'];
                            break;

                        case 'city':
                            // Skip options not in the current country
                            if (array_key_exists('country', $this->arrConfig) && $location['country'] !== $this->arrConfig['country']) {
                                unset($this->filters[$filterField]['options'][$location[$filterField]]);
                                unset($arrSortOptions[$location[$filterField]]);
                            } else {
                                $this->filters[$filterField]['options'][$location[$filterField]]['text'] = ucfirst($location[$filterField]) . ($location['admin_lvl_2'] ? ' (' . $location['admin_lvl_2'] . ')' : '');
                                $arrSortOptions[$location[$filterField]] = $this->filters[$filterField]['options'][$location[$filterField]]['text'];
                            }

                            break;
                        default:
                            break;
                    }

                    // HOOK: add custom logic
                    if (isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'])) {
                        foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                            [$this->filters,$this->arrConfig] = static::importStatic(
                                $callback[0]
                            )->{$callback[1]}($this->filters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                        }
                    }
                }

                // If we have only one option, activate it (may activate other filters conditions)
                if (1 === count($this->filters[$filterField]['options'])) {
                    $opt = $this->filters[$filterField]['options'][array_key_first($this->arrConfig[$filterField]['options'])];
                    $this->arrConfig[$filterField] = $opt['value'];
                    $this->filters[$filterField]['options'][array_key_first($this->arrConfig[$filterField]['options'])]['selected'] = 'selected';
                }
                
                // Sort options
                array_multisort($arrSortOptions, SORT_ASC, $this->filters[$filterField]['options']);
            }

            /**
             ** Adjustements after formatting every filter
             **/

            // If no country was selected, remove city filter
            if (array_key_exists('city', $this->filters) && !array_key_exists('country', $this->arrConfig)) {
                unset($this->filters['city']);
            }
        }
    }

    protected function getListConfig()
    {
        return $this->arrConfig;
    }

    protected function getDefaultListConfig()
    {
        return $this->arrConfigDefault;
    }

    /**
     * Count the total matching items.
     */
    protected function countItems(array $c = []): int
    {
        $c = $c === [] ? $this->getDefaultListConfig() : $c; // we don't want filters to interfere here

        return $this->countLocations($c);
    }

    /**
     * Fetch the matching items.
     *
     * @param array|null $c       configuration. If none provided, the default one will be used
     * @param array|null $options
     */
    protected function fetchItems(
        array|null $c = [],
        int|null $limit = 0,
        int|null $offset = 0,
        $options = []
    ): array|null {
        $c = $c === null || $c === [] ? $this->getDefaultListConfig() : $c; // we don't want filters to interfere here

        $c['limit'] = $limit;
        $c['offset'] = $offset;

        return $this->getLocations($c);
    }

    /**
     * Retrieve all GET keys and turn them into POST
     */
    protected function retrieveGetAttributes(): void
    {
        $arrKeys = Input::getKeys();

        if (!empty($arrKeys)) {
            foreach ($arrKeys as $k) {
                if ("" !== Input::get($k) && !Input::post($k)) {
                    Input::setPost($k, Input::get($k));
                }
            }
        }
    }

    /**
     * Build Pagination.
     *
     * @param int $intTotal Number of items
     *
     * @return [Void]
     */
    protected function buildPagination(
        int $intTotal
    ): void {
        $total = $intTotal - $this->offset;

        // Split the results
        if (

            $this->perPage > 0 && (! property_exists(
                $this,
                'limit'
            ) || $this->limit === null || $this->numberOfItems > $this->perPage)

        ) {
            // Adjust the overall limit
            if (property_exists($this, 'limit') && $this->limit !== null) {
                $total = min($this->limit, $total);
            }

            // Get the current page
            $id = 'page_n' . $this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if (

                $page < 1 || $page > max(
                    ceil($total / $this->perPage),
                    1
                )

            ) {
                throw new Exception(\sprintf(
                    $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['pageNotFound'],
                    Environment::get('uri')
                ));
            }

            // Set limit and offset
            $this->limit = $this->perPage;
            $this->offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($this->offset + $this->limit > $total + $skip) {
                $this->limit = $total + $skip - $this->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get(
                'maxPaginationLinks'
            ) ?? 7, $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }
    }

    protected function getLocationsAjax(): array
    {
        $config = $this->arrConfig;
        $arrFilterFields = unserialize($this->wem_geodata_filters_fields);

        foreach ($arrFilterFields as $filterField) {
            if (Input::post($filterField)) {
                $config[$filterField] = Input::post($filterField);
            }
        }

        return $this->fetchItems($config);
    }

    /**
     * Catch Ajax Requests.
     */
    protected function handleAjaxRequests(): void
    {
        try {
            switch (Input::post('action')) {
                case 'getLocations':
                    $this->buildFilters();
                    $arrLocations = $this->getLocationsAjax();
                    $arrResponse = [
                        'status' => 'success',
                        'locations' => $arrLocations,
                        'markers' => $arrLocations === [] ? [] : $this->buildMarkers($arrLocations),
                    ];
                    break;
                case 'getLocationsList':
                    $this->buildFilters();
                    $arrLocations = $this->fetchItems();
                    $arrResponse = [
                        'status' => 'success',
                        'html' => $this->wem_geodata_map_list !== 'nolist' ? $this->parseLocationsList(
                            $arrLocations
                        ) : '',
                        'json' => json_encode(
                            $arrLocations,
                            JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE
                        ),
                    ];
                    break;
                case 'getLocationsItemsPagined':
                    $this->buildFilters();
                    $arrLocations = $this->fetchItems(
                        null,
                        Input::post('limit') ? (int) Input::post('limit') : 50,
                        Input::post('offset') ? (int) Input::post('offset') : 0
                    );
                    $arrResponse = [
                        'status' => 'success',
                        'html' => $this->parseItems($arrLocations),
                        'json' => json_encode(
                            $arrLocations,
                            JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE
                        ),
                    ];
                    break;
                case 'countLocations':
                    $this->buildFilters();
                    $arrLocations = $this->countItems();
                    $arrResponse = [
                        'status' => 'success',
                        'count' => $this->countItems(),
                    ];
                    break;
                case 'getFilters':
                    $this->buildFilters();
                    $arrResponse = [
                        'status' => 'success',
                        'html' => $this->parseFilters($this->filters, $this->wem_geodata_filters),
                        'json' => json_encode($this->filters),
                    ];
                    break;
                default:
                    throw new Exception(\sprintf(
                        $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['unknownAjaxRequest'],
                        Input::post('action')
                    ));
            }
        } catch (Exception $exception) {
            $arrResponse = [
                'status' => 'error',
                'msg' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ];
        }

        // Add Request Token to JSON answer and return
        $arrResponse['rt'] = System::getContainer()->get(
            'contao.csrf.token_manager'
        )->getDefaultTokenValue();

        echo json_encode($arrResponse);
        exit;
    }
}
