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

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_geodata_filters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_display_map'] = '
    {title_legend},name,type;
    {config_legend},wem_geodata_map,wem_geodata_map_list,wem_geodata_filters;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_list'] = '
    {title_legend},name,type;
    {template_legend:hide},wem_geodata_maps,wem_geodata_filters,perPage,numberOfItems,customTpl,wem_geodata_customTplForGeodataItems;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_reader'] = '
    {title_legend},name,type;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_filters_leftpanel'] = 'wem_geodata_search,wem_geodata_filters_fields';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_filters_above'] = 'wem_geodata_search,wem_geodata_filters_fields';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_filters_below'] = 'wem_geodata_search,wem_geodata_filters_fields';

$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map'],
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_wem_map.title',
    'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_maps'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_maps'],
    'exclude' => true,
    'inputType' => 'select',
    'foreignKey' => 'tl_wem_map.title',
    'eval' => ['chosen' => true, 'mandatory' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql' => 'blob NULL',
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_list'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_list'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['nolist', 'rightpanel', 'below'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_list'],
    'eval' => ['chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(32) NOT NULL default 'nolist'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_filters'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_filters'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['nofilters', 'leftpanel', 'above', 'below'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_filters'],
    'eval' => ['submitOnChange' => true, 'chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(32) NOT NULL default 'nofilters'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_filters_fields'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_filters_fields'],
    'exclude' => true,
    'inputType' => 'select',
    // 'options' => ['category', 'country', 'admin_lvl_1', 'admin_lvl_2', 'city'],
    'options' => [
        'category' => 'category',
        'country' => 'country',
        'admin_lvl_1' => 'admin_lvl_1',
        'admin_lvl_2' => 'admin_lvl_2',
        'city' => 'city',
    ],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_filters_fields'],
    'eval' => ['chosen' => true, 'mandatory' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql' => "blob NULL'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_search'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_search'],
    'exclude' => true,
    'filter' => true,
    'flag' => 1,
    'inputType' => 'checkbox',
    'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
    'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_customTplForGeodataItems'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options_callback' => static function () {
        return \Contao\Controller::getTemplateGroup('mod_wem_geodata_list_item', [], 'mod_wem_geodata_list_item');
    },
    'eval' => ['chosen' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(64) NOT NULL default ''",
];
