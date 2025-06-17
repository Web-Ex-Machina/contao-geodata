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

use Contao\BackendTemplate;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Exception;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemCategory;

/**
 * Front end module "locations map".
 */
class DisplayMap extends Core
{
    /**
     * Map Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_geodata_map';

    /**
     * List Template.
     *
     * @var string
     */
    protected $strListTemplate = 'mod_wem_geodata_list';

    /**
     * Filters.
     *
     * @var array [Available filters]
     */
    protected $filters = [];

    /**
     * Config.
     *
     * @var array [default config]
     */
    protected $arrConfig;

    /**
     * @var array
     */
    protected $arrConfigDefault;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        $request = System::getContainer()->get('request_stack')->getCurrentRequest();

        if ($request && System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($request)) {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['wem_display_map'][0] . ' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id=' . $this->id;

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
            $this->objMap = Map::findById($this->wem_geodata_map);

            if (! $this->objMap) {
                throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noMapFound']);
            }

            // Load the libraries
            ClassLoader::loadLibraries($this->objMap, WEM_GEODATA_COMBINER_VERSION);
            Util::getCountries();

            // Build the config
            $arrMapConfig = [];
            if ($this->objMap->mapConfig) {
                foreach (StringUtil::deserialize($this->objMap->mapConfig) as $arrRow) {
                    if ($arrRow['value'] === 'true') {
                        $varValue = true;
                    } elseif ($arrRow['value'] === 'false') {
                        $varValue = false;
                    } elseif (\is_string($arrRow['value'])) {
                        $varValue = html_entity_decode($arrRow['value']);
                    } else {
                        $varValue = $arrRow['value'];
                    }

                    if (str_contains($arrRow['key'], '_')) {
                        $arrOption = explode('_', $arrRow['key']);
                        $arrMapConfig[$arrOption[0]][$arrOption[1]] = $varValue;
                    } else {
                        $arrMapConfig['map'][$arrRow['key']] = $varValue;
                    }
                }
            }

            // config for locations $arrConfigBase = ['pid' => $this->objMap->id, 'published'

            // => 1, 'onlyWithCoords' => 1]; $arrConfig = $arrConfigBase;

            $this->arrConfig = ['pid' => $this->objMap->id,
                'published' => 1,
                'onlyWithCoords' => 1];
            $this->arrConfigDefault = $this->arrConfig; // keep this one clean, so we load all items disregarding filters values

            // Catch AJAX request
            if (Input::post('TL_AJAX') && (int) $this->id === (int) Input::post('module')) {
                $this->handleAjaxRequests();
            }

            // Gather filters
            $this->buildFilters();
            $this->Template->filters = $this->filters;
            $this->Template->filters_position = $this->wem_geodata_filters;

            $nbItems = $this->countItems();
            $blnLoadInAjax = (int) $this->wem_geodata_map_nbItemsToForceAjaxLoading === 0
                ? false
                : $nbItems > (int) $this->wem_geodata_map_nbItemsToForceAjaxLoading;
            $this->Template->nbItems = $this->countItems();
            $this->Template->nbItemsPerRequest = (int) $this->wem_geodata_map_nbItemsToForceAjaxLoading;

            // Get the jumpTo page
            $this->objJumpTo = PageModel::findById($this->objMap->jumpTo);

            $arrLocations = [];
            $arrMarkers = [];
            if (! $blnLoadInAjax) {
                // Get locations
                $arrLocations = $this->fetchItems();
                // Now we retrieved all the locations, we will regroup the close ones into one
                $arrMarkers = $this->buildMarkers(
                    $arrLocations
                );
            }

            // Get categories
            $arrCategories = $this->getCategories();

            // Send the data to Map template
            $this->Template->mapProvider = $this->objMap->mapProvider;
            $this->Template->geocodingProvider = $this->objMap->geocodingProvider;
            $this->Template->markers = $arrMarkers;
            $this->Template->locations = $arrLocations;
            $this->Template->categories = $arrCategories;
            $this->Template->filters_html = $blnLoadInAjax ? '' : $this->parseFilters(
                $this->filters,
                $this->wem_geodata_filters
            );

            $this->Template->config = $arrMapConfig;

            $this->Template->moduleId = $this->id;
            $this->Template->rt = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
            $this->Template->blnLoadInAjax = $blnLoadInAjax;

            // If the config says so, we will generate a template with a list of the locations
            if ($this->wem_geodata_map_list !== 'nolist') {
                $this->Template->list = $this->parseLocationsList($arrLocations);
            }
        } catch (Exception $exception) {
            $this->Template->error = true;
            $this->Template->msg = $exception->getMessage();
            $this->Template->trace = $exception->getTraceAsString();
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
                    $arrLocations = $this->fetchItems();
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
            $arrResponse = ['status' => 'error',
                'msg' => $exception->getMessage(),
                'trace' => $exception->getTrace()];
        }

        // Add Request Token to JSON answer and return
        $arrResponse['rt'] = System::getContainer()->get(
            'contao.csrf.token_manager'
        )->getDefaultTokenValue();
        echo json_encode($arrResponse);
        exit;
    }

    protected function buildFilters(): void
    {
        // Gather filters
        if ($this->wem_geodata_filters !== 'nofilters') {
            $this->filters = [];
            $locations = MapItem::findItems($this->arrConfig);
            System::loadLanguageFile('tl_wem_map_item');

            if ($this->wem_geodata_search) {
                $this->filters['search'] = [
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
            $arrLocations = [];
            if ($locations instanceof Collection) {
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }
            }

            $arrCountries = Util::getCountries();

            foreach ($arrFilterFields as $filterField) {
                if (Input::get($filterField)) {
                    $this->arrConfig[$filterField] = Input::get($filterField);
                }

                $this->filters[$filterField] = [
                    'label' => \sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][0]),
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][1],
                    'name' => $filterField,
                    'type' => 'select',
                    'options' => [],
                ];

                foreach ($arrLocations as $location) {
                    if (! $location[$filterField]) {
                        if (

                            isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array(
                                $GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']
                            )

                        ) {
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
                            'selected' => (\array_key_exists(
                                $filterField,
                                $this->arrConfig
                            ) && $this->arrConfig[$filterField] === Util::formatStringValueForFilters(
                                (string) $location[$filterField]
                            ) ? 'selected' : ''),
                        ];
                    }

                    switch ($filterField) {
                        case 'city':
                            // $this->filters[$filterField]['options'][$location[$filterField]]['text'] =

                            // $location[$filterField].' ('.$location['admin_lvl_2'].')';
                            $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $location[$filterField] . ($location['admin_lvl_2'] ? ' (' . $location['admin_lvl_2'] . ')' : '');
                            break;
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
                                    }
                                }
                            }

                            break;
                        case 'country':
                            $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[$location[$filterField]] ?? $location[$filterField];
                            break;
                        default:
                            break;
                    }

                    // HOOK: add custom logic
                    if (

                        isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array(
                            $GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']
                        )

                    ) {
                        foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                            [$this->filters,$this->arrConfig] = static::importStatic(
                                $callback[0]
                            )->{$callback[1]}($this->filters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                        }
                    }
                }
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

    protected function buildMarkers(array $arrLocations): array
    {
        $arrMarkers = [];

        foreach ($arrLocations as $l) {
            $arrMarkers[] = [
                'lat' => $l['lat'],
                'lng' => $l['lng'],
                'continent' => $l['continent'],
                'country' => $l['country'],
                'items' => [
                    0 => $l,
                ],
            ];
        }

        return $arrMarkers;
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

    protected function parseLocationsList(array $arrLocations): string
    {
        $objTemplate = new FrontendTemplate(
            $this->wem_geodata_map_list === 'rightpanel' ? 'mod_wem_geodata_list_inmap' : 'mod_wem_geodata_list'
        );
        $objTemplate->locations = $arrLocations;
        $objTemplate->list_position = $this->wem_geodata_map_list;
        $objTemplate->customTplForGeodataItems = $this->wem_geodata_map_list === 'rightpanel' ? 'mod_wem_geodata_list_inmap_item' : 'mod_wem_geodata_list_item';

        if ($this->filters) {
            $objTemplate->filters = $this->filters;
            $objTemplate->filters_position = $this->wem_geodata_filters;
        }

        return $objTemplate->parse();
    }

    protected function parseItem(array $location): string
    {
        $objTemplate = new FrontendTemplate(
            $this->wem_geodata_map_list === 'rightpanel' ? 'mod_wem_geodata_list_inmap_item' : 'mod_wem_geodata_list_item'
        );
        $objTemplate->location = $location;

        return $objTemplate->parse();
    }

    protected function parseItems(array $locations): array
    {
        $arrItems = [];

        foreach ($locations as $location) {
            $arrItems[] = $this->parseItem($location);
        }

        return $arrItems;
    }

    protected function parseFilters(array $filters, string $position): string
    {
        if ($position === 'nofilters') {
            return '';
        }

        $objTemplate = new FrontendTemplate('mod_wem_geodata_map_filters_' . $position);

        $objTemplate->filters_action = '';
        $objTemplate->filters_method = '';
        $objTemplate->filters = $filters;

        return $objTemplate->parse();
    }
}
