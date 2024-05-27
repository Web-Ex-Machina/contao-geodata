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

use WEM\GeoDataBundle\Model\Map;

/*
 * Table tl_wem_map.
 */
$GLOBALS['TL_DCA']['tl_wem_map'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_map_category', 'tl_wem_map_item'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
        'onload_callback' => [
            [\WEM\GeoDataBundle\DataContainer\Map::class, 'onloadCallback'],
        ],
        'onsubmit_callback' => [
            [\WEM\GeoDataBundle\DataContainer\Map::class, 'onsubmitCallback'],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 1,
            'fields' => ['title'],
            'flag' => 1,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'mapProvider'],
            'format' => '%s | %s',
        ],
        'global_operations' => [
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['edit'],
                'href' => 'table=tl_wem_map_item',
                'icon' => 'edit.gif',
            ],
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.gif',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['copy'],
                'href' => 'act=copy',
                'icon' => 'copychilds.gif',
            ],
            'copy_map_item' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['copy_map_item'],
                'href' => 'key=copy_map_item',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        '__selector__' => ['mapProvider', 'geocodingProvider'],
        'default' => '
            {title_legend},title,jumpTo;
            {import_legend},excelPattern;
            {map_legend},mapProvider;
            {geocoding_legend},geocodingProvider;
            {categories_legend},categories;
            {markers_legend},doNotAddItemsToContaoSitemap,doNotAddItemsToContaoSearch;
            {import_legend},updateExistingItems,deleteExistingItemsNotInImportFile;
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'mapProvider_leaflet' => 'mapConfig',
        'mapProvider_gmaps' => 'mapProviderGmapKey,mapConfig',
        'geocodingProvider_gmaps' => 'geocodingProviderGmapKey',
    ],

    // Fields
    'fields' => [
        'id' => [
            'sql' => 'int(10) unsigned NOT NULL auto_increment',
        ],
        'tstamp' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'default' => time(),
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'jumpTo' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['jumpTo'],
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'excelPattern' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['excelPattern'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'load_callback' => [
                [\WEM\GeoDataBundle\DataContainer\Map::class, 'generateExcelPattern'],
            ],
            'sql' => 'blob NULL',
        ],
        'mapProvider' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProvider'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'select',
            'options' => [Map::MAP_PROVIDER_GMAP, Map::MAP_PROVIDER_LEAFLET],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProvider'],
            'eval' => ['helpwizard' => true, 'mandatory' => true, 'submitOnChange' => true, 'chosen' => true, 'includeBlankOption' => true],
            'explanation' => 'wem_geodata_mapProvider',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'mapConfig' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapConfig'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'load_callback' => [
                [\WEM\GeoDataBundle\DataContainer\Map::class, 'getDefaultMapConfig'],
            ],
            'sql' => 'blob NULL',
        ],
        'mapProviderGmapKey' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProviderGmapKey'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'geocodingProvider' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['geocodingProvider'],
            'exclude' => true,
            'inputType' => 'select',
            'options' => [Map::GEOCODING_PROVIDER_NOMINATIM, Map::GEOCODING_PROVIDER_GMAP],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['geocodingProvider'],
            'eval' => ['helpwizard' => true, 'includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true],
            'explanation' => 'wem_geodata_geocodingProvider',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'geocodingProviderGmapKey' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['geocodingProviderGmapKey'],
            'exclude' => true,
            'inputType' => 'textStore',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'encrypt' => true],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {categories_legend},categories
        'categories' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['categories'],
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_map_category',
            'foreignField' => 'pid',
            'eval' => [
                'fields' => ['createdAt', 'title', 'is_default'],
                'headerFields' => [
                    &$GLOBALS['TL_LANG']['tl_wem_map_category']['createdAt'][0],
                    &$GLOBALS['TL_LANG']['tl_wem_map_category']['title'][0],
                    &$GLOBALS['TL_LANG']['tl_wem_map_category']['is_default'][0],
                ],
                'orderField' => 'createdAt DESC',
                'hideButton' => false,
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
            ],
        ],
        'doNotAddItemsToContaoSitemap' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['doNotAddItemsToContaoSitemap'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'doNotAddItemsToContaoSearch' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['doNotAddItemsToContaoSearch'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'updateExistingItems' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['updateExistingItems'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'deleteExistingItemsNotInImportFile' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['deleteExistingItemsNotInImportFile'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
