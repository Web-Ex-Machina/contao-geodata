<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

/*
 * Backend modules.
 */
// \Contao\ArrayUtil::arrayInsert(
array_insert(
    $GLOBALS['BE_MOD'],
    array_search('content', array_keys($GLOBALS['BE_MOD']), true) + 1,
    [
        'wem-geodata' => [
            'wem-maps' => [
                'tables' => ['tl_wem_map', 'tl_wem_map_category', 'tl_wem_map_item', 'tl_wem_map_item_category', 'tl_content', 'tl_wem_map_item_attribute_value'],
                'import' => ['WEM\GeoDataBundle\Backend\Callback', 'importLocations'],
                'download_import_sample' => ['WEM\GeoDataBundle\Backend\Callback', 'downloadImportSample'],
                'export_form' => ['WEM\GeoDataBundle\Backend\Callback', 'exportLocationsForm'],
                'export' => ['WEM\GeoDataBundle\Backend\Callback', 'exportLocations'],
                'geocode' => ['WEM\GeoDataBundle\Backend\Callback', 'geocode'],
                'copy_map_item' => ['WEM\GeoDataBundle\Backend\Callback', 'copyMapItem'],
                'icon' => 'system/bundles/wemgeodata/backend/icon_map_16_c3.png',
            ],
        ],
    ]
);

/*
 * Load icon in Contao 4.2 backend
 */
// if ('BE' === TL_MODE) {
//     if (version_compare(VERSION, '4.4', '<')) {
//         $GLOBALS['TL_CSS'][] = 'bundles/wemgeodata/backend/backend.css';
//     } else {
        $GLOBALS['TL_CSS'][] = 'bundles/wemgeodata/backend/backend_svg.css';
//     }
// }

/*
 * Frontend modules
 */
// \Contao\ArrayUtil::arrayInsert(
array_insert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem_geodata' => [
            'wem_display_map' => 'WEM\GeoDataBundle\Module\DisplayMap',
            'wem_geodata_list' => 'WEM\GeoDataBundle\Module\LocationsList',
            'wem_geodata_reader' => 'WEM\GeoDataBundle\Module\LocationsReader',
        ],
    ]
);

/*
 * Models
 */
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\Map::getTable()] = 'WEM\GeoDataBundle\Model\Map';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\MapItem::getTable()] = 'WEM\GeoDataBundle\Model\MapItem';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\MapItemCategory::getTable()] = 'WEM\GeoDataBundle\Model\MapItemCategory';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\MapItemAttributeValue::getTable()] = 'WEM\GeoDataBundle\Model\MapItemAttributeValue';
$GLOBALS['TL_MODELS'][\WEM\GeoDataBundle\Model\Category::getTable()] = 'WEM\GeoDataBundle\Model\Category';

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = ['WEM\GeoDataBundle\Classes\Util', 'replaceInsertTags'];
