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
 * Table tl_wem_map.
 */
$GLOBALS['TL_DCA']['tl_wem_map'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ctable' => ['tl_wem_map_category', 'tl_wem_item'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
            ],
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
                'href' => 'table=tl_wem_item',
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
            {categories_legend},categories
        ',
    ],

    // Subpalettes
    'subpalettes' => [
        'mapProvider_jvector' => 'mapFile,mapConfig',
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
                ['tl_wem_map', 'generateExcelPattern'],
            ],
            'sql' => 'blob NULL',
        ],
        'mapProvider' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProvider'],
            'default' => '',
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['jvector', 'gmaps', 'leaflet'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapProvider'],
            'eval' => ['helpwizard' => true, 'mandatory' => true, 'submitOnChange' => true, 'chosen' => true, 'includeBlankOption' => true],
            'explanation' => 'wem_locations_mapProvider',
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'mapFile' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapFile'],
            'default' => 'world',
            'exclude' => true,
            'inputType' => 'select',
            'options' => ['world', 'continents', 'africa', 'asia', 'europe', 'fr_regions', 'fr_departments', 'north_america', 'oceania', 'south_america'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapFile'],
            'eval' => ['mandatory' => true, 'chosen' => true],
            'sql' => "varchar(255) NOT NULL default 'world'",
        ],
        'mapConfig' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map']['mapConfig'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'load_callback' => [
                ['tl_wem_map', 'getDefaultMapConfig'],
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
            'options' => ['nominatim', 'gmaps'],
            'reference' => &$GLOBALS['TL_LANG']['tl_wem_map']['geocodingProvider'],
            'eval' => ['helpwizard' => true, 'includeBlankOption' => true, 'submitOnChange' => true, 'chosen' => true],
            'explanation' => 'wem_locations_geocodingProvider',
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
                'fields' => ['createdAt', 'title'],
                'headerFields' => [
                    &$GLOBALS['TL_LANG']['tl_wem_map_category']['createdAt'], 
                    &$GLOBALS['TL_LANG']['tl_wem_map_category']['title']
                ],
                'orderField' => 'createdAt DESC',
                'hideButton' => false,
                'showOperations' => true,
                'operations' => ['edit', 'delete'],
            ],
        ],
    ],
];

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_map extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Generate the default map config array.
     *
     * @param [Array] $varValue
     *
     * @return [Array]
     */
    public function getDefaultMapConfig($varValue, $objDc)
    {
        if (!$varValue) {
            switch ($objDc->activeRecord->mapProvider) {
                // case 'jvector':
                //     $arrConfig = \WEM\GeoDataBundle\Controller\Provider\JVector::getDefaultConfig();
                //     break;

                case 'leaflet':
                    $arrConfig = \WEM\GeoDataBundle\Controller\Provider\Leaflet::getDefaultConfig();
                    break;

                default:
                    $arrConfig = [];
            }

            foreach ($arrConfig as $strKey => $strValue) {
                $varValue[] = ['key' => $strKey, 'value' => $strValue];
            }
        }

        return $varValue;
    }

    /**
     * Generate the default Excel pattern.
     *
     * @param [Array] $varValue
     *
     * @return [Array]
     */
    public function generateExcelPattern($varValue)
    {
        if (!$varValue) {
            $varValue = [
                ['key' => 'title', 'value' => 'A'], ['key' => 'lat', 'value' => 'B'], ['key' => 'lng', 'value' => 'C'], ['key' => 'street', 'value' => 'D'], ['key' => 'postal', 'value' => 'E'], ['key' => 'city', 'value' => 'F'], ['key' => 'region', 'value' => 'G'], ['key' => 'country', 'value' => 'H'], ['key' => 'phone', 'value' => 'I'], ['key' => 'email', 'value' => 'J'], ['key' => 'website', 'value' => 'K'],
            ];
        }

        return $varValue;
    }
}
