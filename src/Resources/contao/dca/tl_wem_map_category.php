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
use WEM\GeoDataBundle\DataContainer\Map;
use WEM\GeoDataBundle\DataContainer\MapCategory;


$this->loadDataContainer(
    'tl_wem_map'
);

/*
 * Table tl_wem_map_category.
 */
$GLOBALS['TL_DCA']['tl_wem_map_category'] = [
    // Config
    'config' => [
        'dataContainer' => DC_Table::class,
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
            [MapCategory::class, 'onsubmitCallback'],
        ],
        'ondelete_callback' => [
            [MapCategory::class, 'ondeleteCallback'],
        ],
    ],

    // List
    'list' => [
        'sorting' => [
            'mode' => DataContainer::MODE_PARENT,
            'fields' => ['createdAt DESC'],
            'headerFields' => ['title'],
            'panelLayout' => 'filter;sort,search,limit',
            'child_record_callback' => [MapCategory::class, 'listItems'],
            'child_record_class' => 'no_padding',
        ],
        'global_operations' => [
            'all' => [
                'href' => 'act=select',
                'class' => 'header_edit_all',
                'attributes' => 'onclick="Backend.getScrollOffset()" accesskey="e"',
            ],
        ],
        'operations' => [
            'edit' => [
                'href' => 'act=edit',
                'icon' => 'edit.svg',
            ],
            'delete' => [
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.($GLOBALS['TL_LANG']['MSC']['deleteConfirm'] ?? null).'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
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
            'flag' => DataContainer::SORT_MONTH_DESC,
            'default' => time(),
            'sql' => "int(10) unsigned NOT NULL default '0'",
        ],

        // {general_legend},title
        'title' => [
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true,
                'maxlength' => 255,
                'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'is_default' => [
            'exclude' => true,
            'filter' => true,
            'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true,
                'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],

        // {marker_legend},marker,markerConfig
        'marker' => [
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true,
                'fieldType' => 'radio',
                'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'markerConfig' => [
            'exclude' => true,
            'inputType' => 'keyValueWizard',
            'load_callback' => [
                [Map::class, 'getDefaultMapConfig'],
            ],
            'sql' => 'blob NULL',
        ],
    ],
];
