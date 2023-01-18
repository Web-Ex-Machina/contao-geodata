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
use Contao\PageModel;
use Contao\System;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Controller\Util;
use WEM\GeoDataBundle\Model\Map;

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
            // ClassLoader::loadLibraries($this->objMap, 1);
            System::getCountries();

            // Build the config
            $arrConfig = [];
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
                        $arrConfig[$arrOption[0]][$arrOption[1]] = $varValue;
                    } else {
                        $arrConfig['map'][$arrRow['key']] = $varValue;
                    }
                }
            }

            // Get the jumpTo page
            $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Get locations
            $arrLocations = $this->getLocations();

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
            $this->Template->markers = $arrMarkers;
            $this->Template->locations = $arrLocations;
            $this->Template->categories = $arrCategories;
            $this->Template->config = $arrConfig;

            // Gather filters
            if ('nofilters' !== $this->wem_geodata_map_filters) {
                System::loadLanguageFile('tl_wem_item');
                $arrFilterFields = unserialize($this->wem_geodata_map_filters_fields);
                $this->filters = [];

                foreach ($arrFilterFields as $f) {
                    if ('search' === $f) {
                        $this->filters[$f] = [
                            'label' => 'Recherche :',
                            'placeholder' => 'Que recherchez-vous ?',
                            'name' => 'search',
                            'type' => 'text',
                            'value' => '',
                        ];
                    } else {
                        $this->filters[$f] = [
                            'label' => sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_item'][$f][0]),
                            'placeholder' => $GLOBALS['TL_LANG']['tl_wem_item'][$f][1],
                            'name' => $f,
                            'type' => 'select',
                            'options' => [],
                        ];

                        foreach ($arrLocations as $l) {
                            if (!$l[$f]) {
                                continue;
                            }

                            if (!\in_array($l[$f], $this->filters[$f]['options'], true)) {
                                switch ($f) {
                                    case 'city':
                                        $this->filters[$f]['options'][] = [
                                            'value' => $l[$f],
                                            'label' => $l[$f].' ('.$l['admin_lvl_2'].')',
                                        ];
                                    break;
                                    default:
                                        $this->filters[$f]['options'][] = $l[$f];
                                }
                            }
                        }
                    }
                }

                $this->Template->filters = $this->filters;
                $this->Template->filters_position = $this->wem_geodata_map_filters;
            }

            // Send the fileMap
            if ('jvector' === $this->objMap->mapProvider
                && '' !== $this->objMap->mapFile
            ) {
                $this->Template->mapFile = $this->objMap->mapFile;
            }

            // If the config says so, we will generate a template with a list of the locations
            if ('nolist' !== $this->wem_geodata_map_list) {
                $objTemplate = new FrontendTemplate($this->strListTemplate);
                $objTemplate->locations = $arrLocations;
                $objTemplate->list_position = $this->wem_geodata_map_list;

                if ($this->filters) {
                    $objTemplate->filters = $this->filters;
                    $objTemplate->filters_position = $this->wem_geodata_map_filters;
                }

                $this->Template->list = $objTemplate->parse();
                $this->Template->list_position = $this->wem_geodata_map_list;
            }
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }
}
