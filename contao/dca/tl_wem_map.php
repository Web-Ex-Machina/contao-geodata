<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

use Contao\DataContainer;
use Contao\DC_Table;
use Contao\System;
use WEM\GeoDataBundle\Model\Map;

/*
 * Table tl_wem_map.
 */
$GLOBALS['TL_DCA']['tl_wem_map'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ctable' => ['tl_wem_map_item', 'tl_wem_map_category'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_SORTED,
            'fields' => ['title'],
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'panelLayout' => 'filter;search,limit',
        ],
        'label' => [
            'fields' => ['title', 'mapProvider'],
            'format' => '%s | %s',
        ],
        'global_operations' => ['all'],
        'operations' => [
            'edit', 
            'children', 
            'copy', 
            'copy_map_item' => [
                'href' => 'key=copy_map_item',
                'icon' => 'copy.svg',
            ], 
            'delete', 
            'show',
        ],
    ],
    'palettes' => [
        '__selector__' => ['mapProvider', 'geocodingProvider'],
        'default' => '
            {title_legend},title,language,jumpTo;
            {map_legend},mapProvider;
            {geocoding_legend},geocodingProvider;
            {categories_legend},categories;
            {markers_legend},doNotAddItemsToContaoSitemap,doNotAddItemsToContaoSearch;
            {import_legend},excelPattern,updateExistingItems,deleteExistingItemsNotInImportFile;
        ',
    ],
    'subpalettes' => [
        'mapProvider_leaflet' => 'mapConfig',
        'mapProvider_gmaps' => 'mapProviderGmapKey,mapConfig',
        'geocodingProvider_nominatim' => 'geocodingProviderNominatimReferer',
    ],
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
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'language' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'select',
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'feEditable' => true, 'feGroup' => 'personal', 'tl_class' => 'w50'],
            'options_callback' => static function () {
                return System::getContainer()->get('contao.intl.locales')->getLocales(null, false);
            },
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'jumpTo' => [
            'exclude' => true,
            'inputType' => 'pageTree',
            'foreignKey' => 'tl_page.title',
            'eval' => ['fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'mapProvider' => [
            'default' => '',
            'exclude' => true,
            'inputType' => 'select',
            'options' => Map::MAP_PROVIDERS,
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProvider'],
            'eval' => ['helpwizard' => true, 'mandatory' => true, 'submitOnChange' => true, 'chosen' => true, 'includeBlankOption' => true],
            'explanation' => 'wem_geodata_mapProvider',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'mapConfig' => [
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'sql' => 'blob NULL',
        ],
        'mapProviderGmapKey' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
            'load_callback' => [
                ['wem.encryption_util', 'decrypt_b64'],
            ],
            'save_callback' => [
                ['wem.encryption_util', 'encrypt_b64'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'geocodingProvider' => [
            'exclude' => true,
            'inputType' => 'select',
            'options' => MAP::GEOCODING_PROVIDERS,
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['geocodingProvider'],
            'eval' => ['helpwizard' => true, 'includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true],
            'explanation' => 'wem_geodata_geocodingProvider',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'geocodingProviderNominatimReferer' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255],
            'load_callback' => [
                ['wem.encryption_util', 'decrypt_b64'],
            ],
            'save_callback' => [
                ['wem.encryption_util', 'encrypt_b64'],
            ],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'categories' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_map_category',
            'foreignField' => 'pid',
            'eval' => [
                'fields' => ['title', 'is_default'],
                'orderField' => 'createdAt DESC',
                'hideButton' => false,
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
            ],
        ],
        'doNotAddItemsToContaoSitemap' => [
            'exclude' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w25 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'doNotAddItemsToContaoSearch' => [
            'exclude' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w25 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'excelPattern' => [
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
            'sql' => 'blob NULL',
        ],
        'updateExistingItems' => [
            'exclude' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w25 m12 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'deleteExistingItemsNotInImportFile' => [
            'exclude' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w25 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
    ],
];
