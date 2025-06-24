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
class LocationsList extends CoreList
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
}
