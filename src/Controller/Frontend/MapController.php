<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    MapController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_map'
)]
class MapController extends ModuleController
{
    public const TYPE = 'wem_geodata_map';

    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $this->loadMap();
        $this->service->loadMapAssets();
        
        $template->list = $this->getListModule();

        $blnLoadInAjax = (int) $this->model->wem_geodata_map_nbItemsToForceAjaxLoading === 0
            ? false
            : $nbItems > (int) $this->model->wem_geodata_map_nbItemsToForceAjaxLoading;

        $this->config = [
            'pid' => $this->map->id,
            'published' => 1,
            'onlyWithCoords' => 1
        ];
        $this->limit = 0;
        $this->offset = 0;
        $this->options = [];

        $arrLocations = [];
        $arrMarkers = [];
        $strFilters = '';

        // If we do not load data in ajax, retrieve items now
        if (!$blnLoadInAjax) {
            // Get locations
            $objItems = $this->findItems();
            $arrItems = $this->formatItems($objItems);

            // Now we retrieved all the locations, we will regroup the close ones into one
            $arrMarkers = $this->buildMarkers($arrItems);

            // Build filters
            $strFilters = $this->getFiltersModule();
        }

        $template->nbItems = $this->countItems();
        $template->nbItemsPerRequest = (int) $this->model->wem_geodata_map_nbItemsToForceAjaxLoading;
        $template->filters_html = $strFilters;
        $template->filters_position = $this->model->wem_geodata_map_filters_position;
        $template->categories = $this->getCategories();
        $template->markers = $arrMarkers;
        $template->locations = $arrItems;
        $template->mapProvider = $this->map->mapProvider;
        $template->geocodingProvider = $this->map->geocodingProvider;
        $template->config = $this->map->getConfig();
        $template->moduleId = $this->model->id;
        $template->rt = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        
        return $template->getResponse();
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
}
