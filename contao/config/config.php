<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

use Contao\ArrayUtil;
use Contao\System;
use WEM\GeoDataBundle\Backend\Callback;
use WEM\GeoDataBundle\Controller\Backend\CopyMapItemController;
use WEM\GeoDataBundle\Controller\Backend\DownloadSampleController;
use WEM\GeoDataBundle\Controller\Backend\ExportController;
use WEM\GeoDataBundle\Controller\Backend\GeocodeController;
use WEM\GeoDataBundle\Controller\Backend\ImportController;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemAttributeValue;
use WEM\GeoDataBundle\Model\MapItemCategory;
use WEM\GeoDataBundle\Module\DisplayMap;
use WEM\GeoDataBundle\Module\LocationsList;
use WEM\GeoDataBundle\Module\LocationsReader;
use WEM\UtilsBundle\Classes\PackageUtil;

if (! defined('WEM_GEODATA_COMBINER_VERSION')) {
    define('WEM_GEODATA_COMBINER_VERSION', PackageUtil::getVersion('webexmachina/contao-geodata'));
}

$scopeMatcher = System::getContainer()->get('wem.scope_matcher');

/*
 * Backend modules.
 */
ArrayUtil::arrayInsert(
    $GLOBALS['BE_MOD'],
    1,
    [
        'wem-geodata' => [
            'wem-maps' => [
                'tables' => [
                    'tl_wem_map',
                    'tl_wem_map_category',
                    'tl_wem_map_item',
                    'tl_wem_map_item_category',
                    'tl_content',
                    'tl_wem_map_item_attribute_value',
                ],
                'import' => [ImportController::class, 'run'],
                'download_import_sample' => [DownloadSampleController::class, 'run'],
                'export' => [ExportController::class, 'run'],
                'geocode' => [GeocodeController::class, 'run'],
                'copy_map_item' => [CopyMapItemController::class, 'run'],
                'icon' => 'system/bundles/wemgeodata/backend/icon_map_16_c3.png',
            ],
        ],
    ]
);

/*
 * Load backend css
 */
if ($scopeMatcher->isBackend()) {
    $GLOBALS['TL_CSS'][] = 'bundles/wemgeodata/backend/backend_svg.css';
}

/*
 * Models
 */
$GLOBALS['TL_MODELS'][Map::getTable()] = Map::class;
$GLOBALS['TL_MODELS'][MapItem::getTable()] = MapItem::class;
$GLOBALS['TL_MODELS'][MapItemCategory::getTable()] = MapItemCategory::class;
$GLOBALS['TL_MODELS'][MapItemAttributeValue::getTable()] = MapItemAttributeValue::class;
$GLOBALS['TL_MODELS'][Category::getTable()] = Category::class;

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
