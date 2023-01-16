<?php

declare(strict_types=1);

/**
 * Altrad Map Bundle for Contao Open Source CMS
 * Copyright (c) 2017-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-altrad-map-bundle
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-altrad-map-bundle/
 */

namespace WEM\GeoDataBundle\Module;

use Contao\BackendTemplate;
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Controller\Util;
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
    protected $strTemplate = 'mod_wem_locations_list';

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
            $this->objMap = Map::findByPk($this->wem_location_map);


            if (!$this->objMap) {
                throw new \Exception('No map found.');
            }

            // Load the libraries
            // ClassLoader::loadLibraries($this->objMap, 1);
            System::getCountries();

            // Build the config
            $arrConfig = ['published' => 1, 'pid' => $this->wem_location_map];

            // Get the jumpTo page
            $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Gather filters
            if ('nofilters' !== $this->wem_location_map_filters) {
                System::loadLanguageFile('tl_wem_item');
                $arrFilterFields = unserialize($this->wem_location_map_filters_fields);
                $this->filters = [];

                foreach ($arrFilterFields as $f) {
                    if ('search' === $f) {
                        $this->filters[$f] = [
                            'label' => 'Recherche :',
                            'placeholder' => 'Indiquez un nom ou un code postal...',
                            'name' => 'search',
                            'type' => 'text',
                            'value' => Input::get($f) ?: '',
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
                                $this->filters[$f]['options'][] = $l[$f];
                            }
                        }
                    }

                    if (Input::get($f)) {
                        $arrConfig[$f] = Input::get($f);
                    }
                }

                $this->Template->filters = $this->filters;
                $this->Template->filters_position = $this->wem_location_map_filters;
                $this->Template->filters_action = Environment::get('request');
                $this->Template->filters_method = "GET";
            }

            // Get locations
            $arrLocations = $this->getLocations($arrConfig);

            // Send the data to Map template
            $this->Template->locations = $arrLocations;
            $this->Template->config = $arrConfig;
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }
}
