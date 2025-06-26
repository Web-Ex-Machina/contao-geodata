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
class DisplayMap extends CoreList
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
            System::loadLanguageFile('tl_wem_map_item');

            // Retrieve the config
            $arrMapConfig = $this->objMap->getConfig();

            // config for locations $arrConfigBase = ['pid' => $this->objMap->id, 'published'
            // => 1, 'onlyWithCoords' => 1]; $arrConfig = $arrConfigBase;
            $this->arrConfig = [
                'pid' => $this->objMap->id,
                'published' => 1,
                'onlyWithCoords' => 1
            ];

            // keep one config "clean", so we load all items disregarding filters values
            $this->arrConfigDefault = $this->arrConfig; 

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
            if (!$blnLoadInAjax) {
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
