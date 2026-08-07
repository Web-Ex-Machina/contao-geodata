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
use WEM\GeoDataBundle\Classes\Util;

/*
 * Table tl_wem_map_item.
 */
$GLOBALS['TL_DCA']['tl_wem_map_item'] = [
    'config' => [
        'dataContainer' => DC_Table::class,
        'ptable' => 'tl_wem_map',
        'ctable' => ['tl_content', 'tl_wem_map_item_attribute_value', 'tl_wem_map_item_category'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['country DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_class' => 'no_padding',
        ],
        'global_operations' => [
            'geocodeAll' => [
                'href' => 'key=geocodeAll',
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'class' => 'header_geocodeAll',
                'attributes' => 'data-confirm="Geocode all?"',
                'primary' => true,
            ],
            'import' => [
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'href' => 'key=import',
                'class' => 'header_css_import',
            ],
            'export' => [
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'href' => 'key=export',
                'class' => 'header_css_import',
            ],
            'all',
        ],
        'operations' => [
            'edit',
            'children',
            'copy',
            'delete',
            'show',
            'toggle',
            'geocode' => [
                'href' => 'key=geocode',
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'primary' => true,
            ],
        ],
    ],
    'palettes' => [
        'default' => '
            {location_legend},title,alias,categories;
            {street_legend},country,admin_lvl_3,admin_lvl_2,admin_lvl_1,city,postal,street;
            {coords_legend},lat,lng;
            {data_legend},picture,teaser;
            {contact_legend},phone,email,website;
            {attributes_legend},attributes;
            {publish_legend},published,publishedAt,publishedUntil
        ',
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
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'categories' => [
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => DataContainer::SORT_ASC,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_map_category.title',
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'multiple' => true, 'mandatory' => true, 'tl_class' => 'w50'],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
        ],
        'lat' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'lng' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'street' => [
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'w100 clr'],
            'sql' => 'text NULL',
        ],
        'postal' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'city' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'admin_lvl_1' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_2' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_3' => [
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'country' => [
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options' => Util::getCountries(),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
        ],
        'picture' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'teaser' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],
        'phone' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'email' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'website' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'attributes' => [
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_map_item_attribute_value',
            'foreignField' => 'pid',
            'eval' => [
                'fields' => ['attribute', 'value'],
                'headerFields' => ['Attribut', 'Valeur'],
                'orderField' => 'createdAt DESC',
                'hideButton' => false,
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
            ],
        ],
        'published' => [
            'exclude' => true,
            'filter' => true,
            'toggle' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12 clr'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'publishedAt' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim',
                'datepicker' => true, 'tl_class' => 'w50 wizard clr'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'publishedUntil' => [
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
    ],
];

// Load JS to handle backend events
$GLOBALS['TL_JAVASCRIPT'][] = 'https://code.jquery.com/jquery-3.3.1.min.js';
$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/backend/backend.js';
