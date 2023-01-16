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

/*
 * Backend modules.
 */
array_insert(
    $GLOBALS['BE_MOD'],
    array_search('content', array_keys($GLOBALS['BE_MOD']), true) + 1,
    [
        'wem-locations' => [
            'wem-maps' => [
                'tables' => ['tl_wem_map', 'tl_wem_map_category', 'tl_wem_location', 'tl_content', 'tl_wem_location_attr_value'],
                'import' => ['WEM\GeoDataBundle\Backend\Callback', 'importLocations'],
                'export' => ['WEM\GeoDataBundle\Backend\Callback', 'exportLocations'],
                'geocode' => ['WEM\GeoDataBundle\Backend\Callback', 'geocode'],
                'icon' => 'system/modules/wem-geodata/assets/icon_map_16_c3.png',
            ],
        ],
    ]
);

/*
 * Load icon in Contao 4.2 backend
 */
if ('BE' === TL_MODE) {
    if (version_compare(VERSION, '4.4', '<')) {
        $GLOBALS['TL_CSS'][] = 'bundles/wem-geodata/backend/backend.css';
    } else {
        $GLOBALS['TL_CSS'][] = 'bundles/wem-geodata/backend/backend_svg.css';
    }
}

/*
 * Frontend modules
 */
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem_locations' => [
            'wem_display_map' => 'WEM\GeoDataBundle\Module\DisplayMap',
            'wem_location_list' => 'WEM\GeoDataBundle\Module\LocationsList',
            'wem_location_reader' => 'WEM\GeoDataBundle\Module\LocationsReader',
        ],
    ]
);

/*
 * Models
 */
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\Map::getTable()] = 'WEM\GeoDataBundle\Model\Map';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\Location::getTable()] = 'WEM\GeoDataBundle\Model\Location';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\LocationAttributeValue::getTable()] = 'WEM\GeoDataBundle\Model\LocationAttributeValue';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\Category::getTable()] = 'WEM\GeoDataBundle\Model\Category';

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['WEM\GeoDataBundle\Controller\Util', 'replaceInsertTags'];