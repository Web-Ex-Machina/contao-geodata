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
use Contao\Environment;
use Contao\Input;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use WEM\GeoDataBundle\Controller\ClassLoader;
use WEM\GeoDataBundle\Model\Category;
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
    protected $strTemplate = 'mod_wem_geodata_list';

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
            $this->maps = Map::findItems([
                'where' => [
                    sprintf('id in (%s)', implode(',', StringUtil::deserialize($this->wem_geodata_maps))),
                ],
            ]);

            if (!$this->maps) {
                throw new \Exception('No maps found.');
            }

            // Load the libraries
            // ClassLoader::loadLibraries($this->objMap, 1);
            System::getCountries();

            // Build the config (do not manage pagination here !)
            $arrConfig = ['published' => 1, 'where' => [sprintf('pid in (%s)', implode(',', StringUtil::deserialize($this->wem_geodata_maps)))]];

            // Get the jumpTo page
            // $this->objJumpTo = PageModel::findByPk($this->objMap->jumpTo);

            // Gather filters
            if ('nofilters' !== $this->wem_geodata_map_filters) {
                System::loadLanguageFile('tl_wem_item');
                $arrFilterFields = unserialize($this->wem_geodata_map_filters_fields);
                $this->filters = [];

                $arrCountries = System::getContainer()->get('contao.intl.countries')->getCountries();
                $locations = Item::findItems($arrConfig);
                $arrLocations = [];
                while ($locations->next()) {
                    $arrLocations[] = $locations->current()->row();
                }

                foreach ($arrFilterFields as $filterField) {
                    if (Input::get($filterField)) {
                        $arrConfig[$filterField] = Input::get($filterField);
                    }
                    if ('search' === $filterField) {
                        $this->filters[$filterField] = [
                            'label' => 'Recherche :',
                            'placeholder' => 'Indiquez un nom ou un code postal...',
                            'name' => 'search',
                            'type' => 'text',
                            'value' => Input::get($filterField) ?: '',
                        ];
                    } else {
                        $this->filters[$filterField] = [
                            'label' => sprintf('%s :', $GLOBALS['TL_LANG']['tl_wem_item'][$filterField][0]),
                            'placeholder' => $GLOBALS['TL_LANG']['tl_wem_item'][$filterField][1],
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
                                'value' => $location[$filterField],
                                'text' => $location[$filterField],
                                'selected' => (Input::get($filterField) && Input::get($filterField) === $location[$filterField] ? 'selected' : ''),
                            ];
                            switch ($filterField) {
                                case 'category':
                                    $objCategory = Category::findByPk($location[$filterField]);
                                    if (!$objCategory) {
                                        return;
                                    }
                                    $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $objCategory->title;
                                break;
                                case 'country':
                                    $this->filters[$filterField]['options'][$location[$filterField]]['text'] = $arrCountries[strtoupper($location[$filterField])] ?? $location[$filterField];
                                break;
                            }
                        }
                    }
                }

                $this->Template->filters = $this->filters;
                $this->Template->filters_position = $this->wem_geodata_map_filters;
                $this->Template->filters_action = Environment::get('request');
                $this->Template->filters_method = 'GET';
            }

            // pagination
            $this->numberOfItems = \count($arrLocations);
            $this->buildPagination(\count($arrLocations));

            // Get locations
            $arrConfig['limit'] = $this->perPage;
            $arrConfig['offset'] = $this->perPage * ((Input::get('page_n'.$this->id) ? (int) Input::get('page_n'.$this->id) : 1) - 1);
            $arrLocations = $this->getLocations($arrConfig);

            // Send the data to Map template
            $this->Template->locations = $arrLocations;
            $this->Template->config = $arrConfig;
            $this->Template->itemCustomTpl = $this->wem_geodata_customTplForGeodataItems ?? 'mod_wem_geodata_list_item';
        } catch (\Exception $e) {
            $this->Template->error = true;
            $this->Template->msg = $e->getMessage();
            $this->Template->trace = $e->getTraceAsString();
        }
    }
}
