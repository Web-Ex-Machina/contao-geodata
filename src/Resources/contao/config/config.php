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

use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Module;
use WEM\GeoDataBundle\Model;
use \WEM\GeoDataBundle\Backend;

if (!\defined('WEM_GEODATA_COMBINER_VERSION')) {
    \define('WEM_GEODATA_COMBINER_VERSION', Util::getCustomPackageVersion('webexmachina/contao-geodata'));
}

/*
 * Backend modules.
 */

Contao\ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    array_search('content', array_keys($GLOBALS['BE_MOD']), true) + 1,
    [
        'wem-geodata' => [
            'wem-maps' => [
                'tables' => ['tl_wem_map', 'tl_wem_map_category', 'tl_wem_map_item', 'tl_wem_map_item_category', 'tl_content', 'tl_wem_map_item_attribute_value'],
                'import' => [Backend\Callback::class, 'importLocations'],
                'download_import_sample' => [Backend\Callback::class, 'downloadImportSample'],
                'export_form' => [Backend\Callback::class, 'exportLocationsForm'],
                'export' => [Backend\Callback::class, 'exportLocations'],
                'geocode' => [Backend\Callback::class, 'geocode'],
                'copy_map_item' => [Backend\Callback::class, 'copyMapItem'],
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
Contao\ArrayUtil::arrayInsert(
    $GLOBALS['FE_MOD'],
    2,
    [
        'wem_geodata' => [
            'wem_display_map' => Module\DisplayMap::class,
            'wem_geodata_list' => Module\LocationsList::class,
            'wem_geodata_reader' => Module\LocationsReader::class,
        ],
    ]
);

/*
 * Models
 */
$GLOBALS['TL_MODELS'][Model\Map::getTable()] = Model\Map::class;
$GLOBALS['TL_MODELS'][Model\MapItem::getTable()] = Model\MapItem::class;
$GLOBALS['TL_MODELS'][Model\MapItemCategory::getTable()] = Model\MapItemCategory::class;
$GLOBALS['TL_MODELS'][Model\MapItemAttributeValue::getTable()] = Model\MapItemAttributeValue::class;
$GLOBALS['TL_MODELS'][Model\Category::getTable()] = Model\Category::class;

/*
 * Hooks
 */
$GLOBALS['TL_HOOKS']['replaceInsertTags'][] = static fn(string $tag) => Util::replaceInsertTags($tag);
$GLOBALS['TL_HOOKS']['generateBreadcrumb'][] = ['wem.geodata.listener.generate_breadcrumb_listener', '__invoke'];

// File Usage bundle
$GLOBALS['FILE_USAGE']['tl_wem_map_item'] = [
    'labelColumn' => ['title'],
    'parent' => false,
    'href' => '/contao?do=wem-maps&table=tl_wem_map_item&act=edit&id=%id%',
];
$GLOBALS['TL_LANG']['FILE_USAGE']['tl_wem_map_item'] = &$GLOBALS['TL_LANG']['WEM']['LOCATIONS']['FILE_USAGE']['tableNameMapItem'];

$GLOBALS['FILE_USAGE']['tl_wem_map_category'] = [
    'labelColumn' => ['title'],
    'parent' => false,
    'href' => '/contao?do=wem-maps&table=tl_wem_map_category&act=edit&id=%id%',
];
$GLOBALS['TL_LANG']['FILE_USAGE']['tl_wem_map_category'] = &$GLOBALS['TL_LANG']['WEM']['LOCATIONS']['FILE_USAGE']['tableNameMapCategory'];
