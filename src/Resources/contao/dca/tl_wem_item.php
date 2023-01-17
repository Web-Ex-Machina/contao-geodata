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

use Contao\StringUtil;
use WEM\GeoDataBundle\Model\Map;

/*
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

/*
 * Table tl_wem_item.
 */
$GLOBALS['TL_DCA']['tl_wem_item'] = [
    // Config
    'config' => [
        'dataContainer' => 'Table',
        'ptable' => 'tl_wem_map',
        'ctable' => ['tl_content', 'tl_wem_item_attr_value'],
        'switchToEdit' => true,
        'enableVersioning' => true,
        'onload_callback' => [
            ['tl_wem_item', 'checkIfGeocodeExists'],
        ],
        'onsubmit_callback' => [
            //array('tl_wem_item', 'fetchCoordinates'),
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
            'child_record_callback' => ['tl_wem_item', 'listItems'],
            'child_record_class' => 'no_padding',
        ],
        'global_operations' => [
            'geocodeAll' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['geocodeAll'],
                'href' => 'key=geocodeAll',
                'class' => 'header_geocodeAll',
                'attributes' => 'onclick="Backend.getScrollOffset()" data-confirm="'.$GLOBALS['TL_LANG']['tl_wem_item']['geocodeAllConfirm'].'"',
                'button_callback' => ['tl_wem_item', 'geocodeAllButtonGlobalOperations'],
            ],
            'import' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['import'],
                'href' => 'key=import',
                'class' => 'header_css_import',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_wem_item', 'importButtonGlobalOperations'],
            ],
            'export' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['export'],
                'href' => 'key=export',
                'class' => 'header_css_import',
                'attributes' => 'onclick="Backend.getScrollOffset()"',
                'button_callback' => ['tl_wem_item', 'exportButtonGlobalOperations'],
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
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['edit'],
                'href' => 'table=tl_content',
                'icon' => 'edit.svg',
            ],
            'editheader' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['editheader'],
                'href' => 'act=edit',
                'icon' => 'header.svg',
            ],
            'copy' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['copy'],
                'href' => 'act=copy',
                'icon' => 'copy.gif',
            ],
            'delete' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['delete'],
                'href' => 'act=delete',
                'icon' => 'delete.gif',
                'attributes' => 'onclick="if(!confirm(\''.$GLOBALS['TL_LANG']['MSC']['deleteConfirm'].'\'))return false;Backend.getScrollOffset()"',
            ],
            'show' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['show'],
                'href' => 'act=show',
                'icon' => 'show.gif',
            ],
            'toggle' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['toggle'],
                'icon' => 'visible.gif',
                'attributes' => 'onclick="Backend.getScrollOffset();return AjaxRequest.toggleVisibility(this,%s)"',
                'button_callback' => ['tl_wem_item', 'toggleIcon'],
            ],
            'geocode' => [
                'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['geocode'],
                'href' => 'key=geocode',
                'icon' => 'bundles/wemgeodata/backend/icon_geocode_16.png',
                'button_callback' => ['tl_wem_item', 'geocodeButtonOperations'],
            ],
        ],
    ],

    // Palettes
    'palettes' => [
        'default' => '
            {location_legend},title,alias,category,published, publishedAt, publishedUntil;
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
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['title'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['mandatory' => true, 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'alias' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['alias'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'alias', 'doNotCopy' => true, 'unique' => true, 'maxlength' => 128, 'tl_class' => 'w50'],
            'save_callback' => [
                ['tl_wem_item', 'generateAlias'],
            ],
            'sql' => "varchar(128) BINARY NOT NULL default ''",
        ],
        'category' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['category'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'flag' => 11,
            'inputType' => 'select',
            'foreignKey' => 'tl_wem_map_category.title',
            'options_callback' => ['tl_wem_item', 'getMapCategories'],
            'eval' => ['chosen' => true, 'includeBlankOption' => true, 'tl_class' => 'w50'],
            'sql' => "int(10) unsigned NOT NULL default '0'",
            'relation' => ['type' => 'hasOne', 'load' => 'lazy'],
        ],
        'published' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['published'],
            'exclude' => true,
            'filter' => true,
            'flag' => 1,
            'inputType' => 'checkbox',
            'eval' => ['doNotCopy' => true, 'tl_class' => 'w50 m12'],
            'sql' => "char(1) NOT NULL default ''",
        ],
        'publishedAt' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['publishedAt'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],
        'publishedUntil' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['publishedUntil'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'datim', 'datepicker' => true, 'tl_class' => 'w50 wizard'],
            'sql' => "varchar(10) NOT NULL default ''",
        ],

        // {coords_legend},lat,lng;
        'lat' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['lat'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'lng' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['lng'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {street_legend},street,postal,city,region,country;
        'street' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['street'],
            'exclude' => true,
            'inputType' => 'textarea',
            'eval' => ['tl_class' => 'w100 clr'],
            'sql' => 'text NULL',
        ],
        'postal' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['postal'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'city' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['city'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'admin_lvl_1' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['admin_lvl_1'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_2' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['admin_lvl_2'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'admin_lvl_3' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['admin_lvl_3'],
            'exclude' => true,
            'filter' => true,
            'inputType' => 'text',
            'eval' => ['tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'country' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['country'],
            'exclude' => true,
            'filter' => true,
            'sorting' => true,
            'inputType' => 'select',
            'options' => System::getCountries(),
            'eval' => ['includeBlankOption' => true, 'chosen' => true, 'tl_class' => 'w50'],
            'sql' => "varchar(2) NOT NULL default ''",
        ],

        // {data_legend},picture,teaser;
        'picture' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['picture'],
            'exclude' => true,
            'inputType' => 'fileTree',
            'eval' => ['filesOnly' => true, 'fieldType' => 'radio', 'tl_class' => 'clr'],
            'sql' => 'binary(16) NULL',
        ],
        'teaser' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['teaser'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'textarea',
            'eval' => ['rte' => 'tinyMCE', 'tl_class' => 'clr'],
            'sql' => 'text NULL',
        ],

        // {contact_legend},phone,fax,cellphone,email;
        'phone' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['phone'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'fax' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['fax'],
            'exclude' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 64, 'tl_class' => 'w50'],
            'sql' => "varchar(64) NOT NULL default ''",
        ],
        'email' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['email'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['maxlength' => 255, 'rgxp' => 'email', 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],

        // {links_legend},website,facebook,twitter,instagram
        'website' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['website'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'facebook' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['facebook'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'twitter' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['twitter'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'instagram' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['instagram'],
            'exclude' => true,
            'search' => true,
            'inputType' => 'text',
            'eval' => ['rgxp' => 'url', 'maxlength' => 255, 'tl_class' => 'w50'],
            'sql' => "varchar(255) NOT NULL default ''",
        ],
        'attributes' => [
            'label' => &$GLOBALS['TL_LANG']['tl_wem_item']['attributes'],
            'inputType' => 'dcaWizard',
            'foreignTable' => 'tl_wem_item_attr_value',
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

/**
 * Provide miscellaneous methods that are used by the data configuration array.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
class tl_wem_item extends \Contao\Backend
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
     * Get and return all the parent map categories.
     *
     * @param [Datacontainer] $dc [Datacontainer]
     *
     * @return [Array] [Categories]
     */
    public function getMapCategories(DataContainer $dc)
    {
        $arrData = [];

        if ($dc->activeRecord->pid) {
            $objCategories = $this->Database->prepare('SELECT id, title FROM tl_wem_map_category WHERE pid = ? ORDER BY createdAt ASC')->execute($dc->activeRecord->pid);

            if (!$objCategories) {
                return [];
            }

            while ($objCategories->next()) {
                $arrData[$objCategories->id] = $objCategories->title;
            }
        }

        return $arrData;
    }

    /**
     * Auto-generate the news alias if it has not been set yet.
     *
     * @throws Exception
     *
     * @return string
     */
    public function generateAlias($varValue, DataContainer $dc)
    {
        $autoAlias = false;

        // Generate alias if there is none
        if ('' === $varValue) {
            $autoAlias = true;
            $slugOptions = [];

            // Read the slug options from the associated page
            if (null !== ($objMap = \WEM\GeoDataBundle\Model\Map::findByPk($dc->activeRecord->pid)) && null !== ($objPage = PageModel::findWithDetails($objMap->jumpTo))) {
                $slugOptions = $objPage->getSlugOptions();
            }

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(StringUtil::prepareSlug($dc->activeRecord->title), $slugOptions);

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-'.$varValue;
            }
        }

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_item WHERE alias=? AND id!=?')
                                   ->execute($varValue, $dc->id)
        ;

        // Check whether the news alias exists
        if ($objAlias->numRows) {
            if (!$autoAlias) {
                throw new Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-'.$dc->id;
        }

        return $varValue;
    }

    /**
     * Adjust DCA if there is no Geocoder for the map.
     */
    public function checkIfGeocodeExists(): void
    {
        $objMap = \WEM\GeoDataBundle\Model\Map::findByPk(\Input::get('id'));

        if ('' === $objMap->geocodingProvider) {
            unset($GLOBALS['TL_DCA']['tl_wem_item']['list']['global_operations']['geocodeAll'], $GLOBALS['TL_DCA']['tl_wem_item']['list']['operations']['geocode']);
        }
    }

    /**
     * Design each row of the DCA.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listItems($arrRow)
    {
        if (!$arrRow['lat'] || !$arrRow['lng']) {
            $strColor = '#ff0000';
        } else {
            $strColor = '#333';
        }

        $strRow = sprintf('<span style="color:%s">%s</span> <span style="color:#888">[%s - %s]</span>', $strColor, $arrRow['title'], $arrRow['city'], $GLOBALS['TL_LANG']['CNT'][$arrRow['country']]);
        $strRow .= '<div class="ajax-results"></div>';

        return $strRow;
    }

    /**
     * Return the "toggle visibility" button.
     *
     * @param array  $row
     * @param string $href
     * @param string $label
     * @param string $title
     * @param string $icon
     * @param string $attributes
     *
     * @return string
     */
    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (\strlen(Input::get('tid'))) {
            $this->toggleVisibility(Input::get('tid'), (1 === Input::get('state')), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_wem_item::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label).'</a> ';
    }

    /**
     * Disable/enable a agence.
     *
     * @param int           $intId
     * @param bool          $blnVisible
     * @param DataContainer $dc
     */
    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null): void
    {
        // Check permissions to edit
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        // Check permissions to publish
        if (!$this->User->hasAccess('tl_wem_item::published', 'alexf')) {
            $this->log('Not enough permissions to publish/unpublish agence item ID "'.$intId.'"', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new Versions('tl_wem_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_item']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_item']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, ($dc ?: $this));
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, ($dc ?: $this));
                }
            }
        }

        // Update the database
        $this->Database->prepare('UPDATE tl_wem_item SET tstamp='.time().", published='".($blnVisible ? '1' : '')."' WHERE id=?")
                       ->execute($intId)
        ;

        $objVersions->create();
        $this->log('A new version of record "tl_wem_item.id='.$intId.'" has been created'.$this->getParentEntries('tl_wem_item', $intId), __METHOD__, TL_GENERAL);
    }

    public function importButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, array $rootIds): string
    {
        $objMap = Map::findByPk(\Contao\Input::get('id'));
        if (!$objMap
        || null === $objMap->excelPattern
        || empty(StringUtil::deserialize($objMap->excelPattern))
        ) {
            return '';
        }

        $url = $this->addToUrl($href);

        return sprintf('<a href="%s" title="%s" class="%s" %s>%s</a>', $url, StringUtil::specialchars($title), $class, $attributes, $label);
    }

    public function exportButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, array $rootIds): string
    {
        $objMap = Map::findByPk(\Contao\Input::get('id'));
        if (!$objMap
        || null === $objMap->excelPattern
        || empty(StringUtil::deserialize($objMap->excelPattern))
        ) {
            return '';
        }

        $url = $this->addToUrl($href);

        return sprintf('<a href="%s" title="%s" class="%s" %s>%s</a>', $url, StringUtil::specialchars($title), $class, $attributes, $label);
    }

    public function geocodeAllButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, array $rootIds): string
    {
        $objMap = Map::findByPk(\Contao\Input::get('id'));
        if (!$objMap
        || null === $objMap->geocodingProvider
        ) {
            return '';
        }

        $url = $this->addToUrl($href);

        return sprintf('<a href="%s" title="%s" class="%s" %s>%s</a>', $url, StringUtil::specialchars($title), $class, $attributes, $label);
    }

    public function geocodeButtonOperations(
        array $data,
        ?string $href,
        string $label,
        string $title,
        ?string $icon,
        string $attributes,
        string $table,
        ?array $arrRootIds,
        ?array $arrChildRecordIds,
        bool $blnCircularReference,
        ?string $strPrevious,
        ?string $strNext,
        Contao\DataContainer $dc
    ): string {
        $objMap = Map::findByPk(\Contao\Input::get('id'));
        if (!$objMap
        || null === $objMap->geocodingProvider
        ) {
            return '';
        }

        return sprintf('<a href="%s" title="%s" %s>%s</a> ', $href, StringUtil::specialchars($title), $attributes, \Contao\Image::getHtml($icon, $label));
    }
}
