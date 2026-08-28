<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

use Contao\Controller;
use Contao\DataContainer;

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_geodata_addList';
$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_geodata_addFilters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_map'] = '
    {title_legend},name,headline,type;
    {config_legend},wem_geodata_map,wem_geodata_map_nbItemsToForceAjaxLoading;
    {list_legend},wem_geodata_addList;
    {filters_legend},wem_geodata_addFilters;
    {image_legend:hide},imgSize;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_list'] = '
    {title_legend},name,headline,type;
    {config_legend},wem_geodata_map,perPage,numberOfItems;
    {filters_legend},wem_geodata_addFilters;
    {image_legend:hide},imgSize;
    {template_legend:hide},customTpl,wem_geodata_item_template;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_reader'] = '
    {title_legend},name,headline,type;
    {config_legend},wem_geodata_map,overviewPage,customLabel;
    {image_legend:hide},imgSize;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_filters'] = '
    {title_legend},name,headline,type;
    {config_legend},jumpTo,wem_geodata_map,wem_geodata_filters_fields,wem_geodata_hideFiltersWithNoResults;
    {search_legend},wem_geodata_addSearch;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_addList'] = 'wem_geodata_list_module,wem_geodata_map_list_position';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_addFilters'] = 'wem_geodata_filters_module,wem_geodata_map_filters_position';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_wem_map.title',
    'eval' => [
        'includeBlankOption' => true,
        'chosen' => true,
        'mandatory' => true,
        'tl_class' => 'w50',
    ],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_nbItemsToForceAjaxLoading'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => [
        'mandatory' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "int(10) NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_addList'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'doNotCopy' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_list_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_list_position'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['nolist', 'rightpanel', 'below'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_list_position'],
    'eval' => [
        'chosen' => true,
        'mandatory' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(32) NOT NULL default 'nolist'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_addFilters'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
    'inputType' => 'checkbox',
    'eval' => [
        'submitOnChange' => true,
        'doNotCopy' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_filters_module'] = [
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_module.name',
    'eval' => ['mandatory' => true],
    'sql' => 'int(10) unsigned NOT NULL default 0',
    'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_filters_position'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['', 'inmap', 'above', 'below'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_filters_position'],
    'eval' => [
        'chosen' => true,
        'mandatory' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(32) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_filters_fields'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => [
        'category' => 'category',
        'country' => 'country',
        'admin_lvl_1' => 'admin_lvl_1',
        'admin_lvl_2' => 'admin_lvl_2',
        'city' => 'city',
    ],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_filters_fields'],
    'eval' => [
        'chosen' => true,
        'mandatory' => true,
        'multiple' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "blob NULL'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_hideFiltersWithNoResults'] = [
    'exclude' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'clr'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_addSearch'] = [
    'exclude' => true,
    'filter' => true,
    'flag' => DataContainer::SORT_INITIAL_LETTER_ASC,
    'inputType' => 'checkbox',
    'eval' => [
        'doNotCopy' => true,
        'tl_class' => 'w50 m12'
    ],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_item_template'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static fn () => Controller::getTemplateGroup(
        'wem_geodata_item_template',
        [],
        'wem_geodata_item_template_default'
    ),
    'eval' => [
        'chosen' => true,
        'tl_class' => 'w50'
    ],
    'sql' => "varchar(64) NOT NULL default 'wem_geodata_item_template_default'",
];