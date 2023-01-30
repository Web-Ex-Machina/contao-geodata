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
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

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
    protected $filters;

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['wem_display_map'][0]).' ###';
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
            $this->objMap = Map::findByPk($this->wem_geodata_map);

            if (!$this->objMap) {
                throw new \Exception('No map found.');
            }

            // Load the libraries
            ClassLoader::loadLibraries($this->objMap, 1);
            Util::getCountries();

            // Build the config
            $arrMapConfig = [];
            if ($this->objMap->mapConfig) {
                foreach (deserialize($this->objMap->mapConfig) as $arrRow) {
                    if ('true' === $arrRow['value']) {
                        $varValue = true;
                    } elseif ('false' === $arrRow['value']) {
                        $varValue = false;
                    } elseif (\is_string($arrRow['value'])) {
                        $varValue = html_entity_decode($arrRow['value']);
                    } else {
                        $varValue = $arrRow['value'];
                    }

                    if (false !== strpos($arrRow['key'], '_')) {
                        $arrOption = explode('_', $arrRow['key']);
                        $arrMapConfig[$arrOption[0]][$arrOption[1]] = $varValue;
                    } else {
                        $arrMapConfig['map'][$arrRow['key']] = $varValue;
                    }
                }
            }

            // config for locations
            $arrConfigBase = ['pid' => $this->objMap->id, 'published' => 1, 'onlyWithCoords' => 1];
            $arrConfig = $arrConfigBase;

            // Gather filters
            if ('nofilters' !== $this->wem_geodata_filters) {
                $this->filters = [];
                $locations = MapItem::findItems($arrConfig);
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
                        $arrConfig['search'] = Input::get('search');
                    }
                }

                $arrFilterFields = unserialize($this->wem_geodata_filters_fields);
                $arrLocations = [];
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }

                $arrCountries = Util::getCountries();
                foreach ($arrFilterFields as $filterField) {
                    if (Input::get($filterField)) {
                        $arrConfig[$filterField] = Input::get($filterField);
                    }
                    $this->filters[$filterField] = [
                        'label' => sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][0]),
                        'placeholder' => $GLOBALS['TL_LANG']['tl_wem_map_item'][$filterField][1],
                        'name' => $filterField,
                        'type' => 'select',
                        'options' => [],
                    ];

                    foreach ($arrLocations as $location) {
                        if (!$location[$filterField]) {
                            continue;
                        }

                        if (\array_key_exists($location[$filterField], $this->filters[$filterField]['options'])) {
                            continue;
                        }
                        $this->filters[$filterField]['options'][$location[$filterField]] = [
                            'value' => str_replace([' ', '.'], '_', mb_strtolower($location[$filterField], 'UTF-8')),
                            'text' => $location[$filterField],
                            'selected' => (\array_key_exists($filterField, $arrConfig) && $arrConfig[$filterField] === str_replace([' ', '.'], '_', mb_strtolower($location[$filterField], 'UTF-8')) ? 'selected' : ''),
                        ];
                        switch ($filterField) {
                            case 'city':
                                $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $location[$filterField].' ('.$location['admin_lvl_2'].')';
                            break;
                            case 'category':
                                $objCategory = Category::findByPk($location[$filterField]);
                                if ($objCategory) {
                                    $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $objCategory->title;
                                    // $this->filters[$filterField]['options'][$location[$filterField]]['value'] = $objCategory->title;
                                }
                            break;
                            case 'country':
                                $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[$location[$filterField]] ?? $location[$filterField];
                            break;
                        }
                    }
                }

                $this->Template->filters = $this->filters;
                $this->Template->filters_position = $this->wem_geodata_filters;
            }

            // Get the jumpTo page
            $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Get locations (will be filtered by the map)
            $arrLocations = $this->getLocations($arrConfigBase);

            // Get categories
            $arrCategories = $this->getCategories();

            // Now we retrieved all the locations, we will regroup the close ones into one
            $arrMarkers = [];
            $distToMerge = $this->wem_geodata_distToMerge ?: 0; // in m

            foreach ($arrLocations as $l) {
                if ($distToMerge > 0) {
                    // For each markers we will need to check the proximity with the other markers
                    // If it's too close, we will merge them and place the marker on the middle of them
                    // Nota 1 : Maybe we shall regroup them before moving the markers (because we could have more and more unprecise markers ?)
                    foreach ($arrMarkers as $k => $m) {
                        // First make sure we stay in the same country
                        // Either way, we will hide items too close from a same border
                        if ($m['country']['code'] !== $l['country']['code']) {
                            continue;
                        }

                        // Calculate the distance between the current location and the markers stored
                        $d = Util::vincentyGreatCircleDistance(
                            $l['lat'],
                            $l['lng'],
                            $m['lat'],
                            $m['lng']
                        );

                        // If proximity too close :
                        // add the location to this marker and continue
                        // adjust marker pos
                        if ($d < $distToMerge) {
                            $arrMarkers[$k]['lat'] = ($l['lat'] + $m['lat']) / 2;
                            $arrMarkers[$k]['lng'] = ($l['lng'] + $m['lng']) / 2;
                            $arrMarkers[$k]['items'][] = $l;
                            continue 2;
                        }
                    }
                }

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

            // Send the data to Map template
            $this->Template->mapProvider = $this->objMap->mapProvider;
            $this->Template->geocodingProvider = $this->objMap->geocodingProvider;
            $this->Template->markers = $arrMarkers;
            $this->Template->locations = $arrLocations;
            $this->Template->categories = $arrCategories;
            $this->Template->config = $arrMapConfig;

            // If the config says so, we will generate a template with a list of the locations
            if ('nolist' !== $this->wem_geodata_map_list) {
                $objTemplate = new FrontendTemplate('rightpanel' === $this->wem_geodata_map_list ? 'mod_wem_geodata_list_inmap' : 'mod_wem_geodata_list');
                $objTemplate->locations = $arrLocations;
                $objTemplate->list_position = $this->wem_geodata_map_list;
                $objTemplate->customTplForGeodataItems = 'mod_wem_geodata_list_item';

                if ($this->filters) {
                    $objTemplate->filters = $this->filters;
                    $objTemplate->filters_position = $this->wem_geodata_filters;
                }
                $this->Template->list = $objTemplate->parse();
            }
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }
}
