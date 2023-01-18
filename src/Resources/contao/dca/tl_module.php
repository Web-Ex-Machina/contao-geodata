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

$GLOBALS['TL_DCA']['tl_module']['palettes']['__selector__'][] = 'wem_geodata_map_filters';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_display_map'] = '
    {title_legend},name,type;
    {config_legend},wem_geodata_map,wem_geodata_map_list,wem_geodata_map_filters,wem_geodata_distToMerge;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_list'] = '
    {title_legend},name,type;
    {template_legend:hide},wem_geodata_maps,wem_geodata_map_filters,perPage,numberOfItems,customTpl,wem_geodata_customTplForGeodataItems;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';
$GLOBALS['TL_DCA']['tl_module']['palettes']['wem_geodata_reader'] = '
    {title_legend},name,type;
    {template_legend:hide},customTpl;
    {protected_legend:hide},protected;
    {expert_legend:hide},guests,cssID
';

$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_map_filters_inmap'] = 'wem_geodata_map_filters_fields';
$GLOBALS['TL_DCA']['tl_module']['subpalettes']['wem_geodata_map_filters_inlist'] = 'wem_geodata_map_filters_fields';

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
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_filters'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_filters'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['nofilters', 'inmap', 'inlist'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_filters'],
    'eval' => ['submitOnChange' => true, 'chosen' => true, 'mandatory' => true, 'tl_class' => 'w50'],
    'sql' => "varchar(32) NOT NULL default 'nofilters'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_map_filters_fields'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_filters_fields'],
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['search', 'category', 'country', 'admin_lvl_1', 'admin_lvl_2', 'city'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_map_filters_fields'],
    'eval' => ['chosen' => true, 'mandatory' => true, 'multiple' => true, 'tl_class' => 'w50'],
    'sql' => "blob NULL'",
];
$GLOBALS['TL_DCA']['tl_module']['fields']['wem_geodata_distToMerge'] = [
    'label' => &$GLOBALS['TL_LANG']['tl_module']['wem_geodata_distToMerge'],
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "int(10) unsigned NOT NULL default '0'",
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
