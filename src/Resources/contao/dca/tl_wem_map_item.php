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

/*
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

/*
 * Table tl_wem_map_item.
 */
$GLOBALS['TL_DCA']['tl_wem_map_item'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_map',
        'ctable' => ['tl_content', 'tl_wem_map_item_attribute_value', 'tl_wem_map_item_category'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onload_callback' => [
            [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'checkIfGeocodeExists'],
        ],
        'onsubmit_callback' => [
            //array(\WEM\GeoDataBundle\DataContainer\MapItem::class, 'fetchCoordinates'),
        ],
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['country DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'listItems'],
            'child_record_class' => 'no_padding',
        ],
        'global_operations' => [
            'geocodeAll' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['geocodeAll'],
                'href' => 'key=geocodeAll',
                'class' => 'header_geocodeAll',
                'attributes' => 'onclick="Backend.getScrollOffset()" data-confirm="'.$GLOBALS['TL_LANG']['tl_wem_map_item']['geocodeAllConfirm'].'"',
                'button_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'geocodeAllButtonGlobalOperations'],
            ],
            'import' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['import'],
                'href' => 'key=import',
                'class' => 'header_css_import',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'importButtonGlobalOperations'],
            ],
            'export' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['export'],
                'href' => 'key=export_form',
                'class' => 'header_css_import',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'exportButtonGlobalOperations'],
            ],
            'all' => [
                'label' => &$GLOBALS['TL_LANG']['MSC']['all'],
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['edit'],
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['toggle'],
                'icon' => 'visible.gif',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'toggleIcon'],
            ],
            'geocode' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['geocode'],
                'href' => 'key=geocode',
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'button_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'geocodeButtonOperations'],
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {location_legend},title,alias,category,categories,published, publishedAt, publishedUntil;
            {street_legend},country,admin_lvl_1,admin_lvl_2,admin_lvl_3,city,postal,street;
            {coords_legend},lat,lng;
            {data_legend},picture,teaser;
            {contact_legend},phone,fax,email;
            {links_legend},website,facebook,twitter,instagram;
            {attributes_legend},attributes
        ',
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
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        // {location_legend},title,alias,category,published;
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['alias'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'category' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['category'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 11,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_map_category.title',
            'options_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'getMapCategories'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'categories' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['categories'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 11,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_map_category.title',
            'options_callback' => [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'getMapCategories'],
            'save_callback' => [
                [\WEM\GeoDataBundle\DataContainer\MapItem::class, 'syncMapItemCategoryPivotTable'],
            ],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'multiple' => true, 'tl_class' => 'w50'],
            'sql' => 'blob NULL',
            'relation' => ['type' => 'belongsTo', 'load' => 'eager'],
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['published'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'publishedAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['publishedAt'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'publishedUntil' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['publishedUntil'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],

        // {coords_legend},lat,lng;
        'lat' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['lat'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'lng' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['lng'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {street_legend},street,postal,city,region,country;
        'street' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['street'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'w100 clr'],
            'sql' => 'text NULL',
        ],
        'postal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['postal'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'city' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['city'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'admin_lvl_1' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['admin_lvl_1'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_2' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['admin_lvl_2'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_3' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['admin_lvl_3'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'country' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['country'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options' => \WEM\GeoDataBundle\Classes\Util::getCountries(),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
        ],

        // {data_legend},picture,teaser;
        'picture' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['picture'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['teaser'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],

        // {contact_legend},phone,fax,cellphone,email;
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['phone'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'fax' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['fax'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['email'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {links_legend},website,facebook,twitter,instagram
        'website' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['website'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'facebook' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['facebook'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'twitter' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['twitter'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'instagram' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['instagram'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'attributes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_item']['attributes'],
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
    ],
];

// Load JS to handle backend events
$GLOBALS['TL_JAVASCRIPT'][] = 'https://code.jquery.com/jquery-3.3.1.min.js';
$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/backend/backend.js';
