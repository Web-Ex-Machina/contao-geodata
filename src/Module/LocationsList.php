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
use Contao\Combiner;
use Contao\Environment;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use Exception;
use WEM\GeoDataBundle\Classes\Util;
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

    /**
     * @var array
     */
    protected $arrConfig;

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

            $objTemplate->wildcard = '### ' . $GLOBALS['TL_LANG']['FMD']['wem_display_list'][0] . ' ###';
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
            if (! $this->wem_geodata_maps) {
                throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noMapConfigured']);
            }

            // Load the map
            $this->maps = Map::findItems([
                'where' => [
                    \sprintf('tl_wem_map.id in (%s)', implode(',', StringUtil::deserialize($this->wem_geodata_maps))),
                ],
            ]);

            if (! $this->maps instanceof Collection) {
                throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noMapFound']);
            }

            $this->objMap = $this->maps->first();

            // Build the config (do not manage pagination here !)
            $this->arrConfig = ['published' => 1,
                'where' => [
                    \sprintf(
                        '%s.pid in (%s)',
                        MapItem::getTable(),
                        implode(',', StringUtil::deserialize($this->wem_geodata_maps))
                    ),
                ]];

            // Catch AJAX request
            if (Input::post('TL_AJAX') && $this->id === Input::post('module')) {
                $this->handleAjaxRequests();
            }

            $limit = null;
            $offset = (int) $this->skipFirst;

            // Maximum number of items
            if ($this->numberOfItems > 0) {
                $limit = $this->numberOfItems;
            }

            // Load the libraries ClassLoader::loadLibraries($this->objMap, 2);
            $objCssCombiner = new Combiner();
            $objCssCombiner->add('bundles/wemgeodata/css/default.css', WEM_GEODATA_COMBINER_VERSION);
            $GLOBALS['TL_HEAD'][] = \sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());
            Util::getCountries();

            // Get the jumpTo page $this->objJumpTo =

            // PageModel::findByPk($this->objMap->jumpTo); Gather filters
            $this->Template->filters = $this->buildFilters();
            $this->Template->filters_position = $this->wem_geodata_filters;
            $this->Template->filters_action = Environment::get('request');
            $this->Template->filters_method = 'GET';

            // pagination
            $this->numberOfItems = $this->countItems();
            if ($this->numberOfItems === 0) {
                throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
            }

            $blnLoadInAjax = (int) $this->wem_geodata_map_nbItemsToForceAjaxLoading === 0
                            ? false
                            : $this->numberOfItems > (int) $this->wem_geodata_map_nbItemsToForceAjaxLoading;
            $this->Template->blnLoadInAjax = $blnLoadInAjax;

            $this->buildPagination($this->numberOfItems);

            // Get locations $this->arrConfig['limit'] = $this->perPage;
            $limit = $this->perPage ?: $limit;
            // $this->arrConfig['offset'] = $this->perPage * ((Input::get('page_n'.$this->id)

            // ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            $offset = $this->perPage * ((Input::get(
                'page_n' . $this->id
            ) ? (int) Input::get(
                'page_n' . $this->id
            ) : 1) - 1);
            // $arrLocations = $this->getLocations($this->arrConfig);
            $arrLocations = $this->fetchItems(
                null,
                $limit ?: 0,
                $offset
            );

            $this->Template->locations = $arrLocations;

            // Get categories
            $arrCategories = $this->getCategories();

            $this->Template->categories = $arrCategories;

            // Add the items if (!empty($arrLocations)) {     $this->Template->locations =

            // $this->parseItems($arrLocations, $this->wem_geodata_customTplForGeodataItems);

            // } Send the data to Map template
            $this->Template->config = $this->arrConfig;
            $this->Template->customTplForGeodataItems = empty($this->wem_geodata_customTplForGeodataItems) ? 'mod_wem_geodata_list_item' : $this->wem_geodata_customTplForGeodataItems;
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
                    $arrResponse = [
                        'status' => 'success',
                        'locations' => $this->getLocationsAjax(),
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

    protected function buildFilters(): array
    {
        $arrFilters = [];
        if ($this->wem_geodata_filters_present) {
            $locations = MapItem::findItems($this->arrConfig);
            System::loadLanguageFile('tl_wem_map_item');

            if ($this->wem_geodata_search) {
                $arrFilters['search'] = [
                    // 'label' => 'Recherche :', 'placeholder' => 'Indiquez un nom ou un code postal...',
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
            if ($locations instanceof Collection) {
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }
            }

            foreach ($arrFilterFields as $filterField) {
                if (Input::get($filterField)) {
                    $this->arrConfig[$filterField] = Input::get($filterField);
                }

                $arrFilters[$filterField] = [
                    'label' => \sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][0]),
                    'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][1],
                    'name' => $filterField,
                    'type' => 'select',
                    'options' => [],
                ];

                foreach ($arrLocations as $location) {
                    if (! $location[$filterField]) {
                        // HOOK: add custom logic
                        if (

                            isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array(
                                $GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']
                            )

                        ) {
                            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                                [$arrFilters, $this->arrConfig] = static::importStatic(
                                    $callback[0]
                                )->{$callback[1]}($arrFilters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
                            }
                        }

                        continue;
                    }

                    if (\array_key_exists($location[$filterField], $arrFilters[$filterField]['options'])) {
                        continue;
                    }

                    if ($filterField !== 'category') {
                        $arrFilters[$filterField]['options'][$location[$filterField]] = [
                            'value' => $location[$filterField],
                            'text' => $location[$filterField],
                            'selected' => (Input::get($filterField) && (Input::get(
                                $filterField
                            ) === $location[$filterField] || Input::get(
                                $filterField
                            ) === Util::formatStringValueForFilters(
                                (string) $location[$filterField]
                            )) ? 'selected' : ''),
                        ];
                    }

                    switch ($filterField) {
                        case 'city':
                            $arrFilters[$filterField]['options'][$location[$filterField]]['value'] = $location[$filterField];
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $location[$filterField] . ($location['admin_lvl_2'] ? ' (' . $location['admin_lvl_2'] . ')' : '');
                            break;
                        case 'category':
                            $mapItemCategories = MapItemCategory::findItems(['pid' => $location['id']]);
                            if ($mapItemCategories instanceof Collection) {
                                while ($mapItemCategories->next()) {
                                    $objCategory = Category::findById($mapItemCategories->category);
                                    if ($objCategory) {
                                        $arrFilters[$filterField]['options'][$objCategory->id]['text'] = $objCategory->title;
                                        $arrFilters[$filterField]['options'][$objCategory->id]['value'] = Util::formatStringValueForFilters(
                                            (string) $objCategory->title
                                        );
                                        $arrFilters[$filterField]['options'][$objCategory->id]['selected'] = (\array_key_exists(
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
                            $arrFilters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[strtolower(
                                $location[$filterField]
                            )] ?? $location[$filterField];
                            break;
                        default:
                            // HOOK: add custom logic
                            if (

                                isset($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']) && \is_array(
                                    $GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION']
                                )

                            ) {
                                foreach ($GLOBALS['TL_HOOKS']['WEMGEODATABUILDFILTERSSINGLEFILTEROPTION'] as $callback) {
                                    [$arrFilters, $this->arrConfig] = static::importStatic(
                                        $callback[0]
                                    )->{$callback[1]}($arrFilters, $this->arrConfig, $filterField, (string) $location[$filterField], $location, $this);
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
     */
    protected function countItems(array $c = []): int
    {
        $c = $c === [] ? $this->getListConfig() : $c;

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
    protected function fetchItems(
        array|null $c = [],
        $limit = 0,
        $offset = 0,
        $options = []
    ): array|null {
        $c = $c === null || $c === [] ? $this->getListConfig() : $c;

        $c['limit'] = $limit;
        $c['offset'] = $offset;

        return $this->getLocations($c);
    }

    /**
     * Parse multiple items.
     */
    protected function parseItems(
        array $objItems,
        string|null $strTemplate = 'mod_wem_geodata_list_item'
    ): array {
        $limit = \count($objItems);
        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrItems = [];

        foreach ($objItems as $location) {
            $arrItems[] = $this->parseItem(
                $location,
                $strTemplate,
                (++$count === 1 ? ' first' : '') . ($count === $limit ? ' last' : '') . ($count % 2 === 0 ? ' odd' : ' even'),
                $count
            );
        }

        return $arrItems;
    }

    protected function parseItem(
        array $objItem,
        $strTemplate = 'mod_wem_geodata_list_item',
        $strClass = '',
        $intCount = 0
    ) {
        $objTemplate = new FrontendTemplate($strTemplate);
        $objTemplate->setData($objItem);

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount;

        return $objTemplate->parse();
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
