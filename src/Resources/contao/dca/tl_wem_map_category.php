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

use WEM\GeoDataBundle\DataContainer\Map;
use WEM\GeoDataBundle\DataContainer\MapCategory;

$this->loadDataContainer('tl_wem_map');

/*
 * Table tl_wem_map_category.
 */
$GLOBALS['TL_DCA']['tl_wem_map_category'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_map',
        'switchToEdit' => true,
        'enableVersioning' => true,
        'sql' => [
            'keys' => [
                'id' => 'primary',
                'pid' => 'index',
            ],
        ],
        'onsubmit_callback' => [
            static function (\Contao\DataContainer $dc) : void {
                (new MapCategory())->onsubmitCallback($dc);
            },
        ],
        'ondelete_callback' => [
            static function (\Contao\DataContainer $dc) : void {
                (new MapCategory())->ondeleteCallback($dc);
            },
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => 4,
            'fields' => ['createdAt DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => static fn(array $row): string => (new MapCategory())->listItems($row),
            'child_record_class' => 'no_padding',
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['edit'],
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {general_legend},title,is_default;
            {marker_legend},marker,markerConfig
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
        'pid' => [
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],
        'createdAt' => [
            'flag' => 8,
            'default' => time(),
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        // {general_legend},title
        'title' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'is_default' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['is_default'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],

        // {marker_legend},marker,markerConfig
        'marker' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['marker'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'markerConfig' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_map_category']['markerConfig'],
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'load_callback' => [
                static fn(array $varValue, $objDc): array => (new Map())->getDefaultMapConfig($varValue, $objDc),
            ],
            'sql' => 'blob NULL',
        ],
    ],
];
