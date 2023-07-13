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

namespace WEM\GeoDataBundle\DataContainer;

use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use WEM\GeoDataBundle\Model\Map;

class MapItem extends CoreContainer
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

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_map_item WHERE alias=? AND id!=?')
                                   ->execute($varValue, $dc->id)
        ;

        // Check whether the news alias exists
        if ($objAlias->numRows) {
            if (!$autoAlias) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
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
        $objMap = \WEM\GeoDataBundle\Model\Map::findByPk(Input::get('id'));

        if ('' === $objMap->geocodingProvider) {
            unset($GLOBALS['TL_DCA']['tl_wem_map_item']['list']['global_operations']['geocodeAll'], $GLOBALS['TL_DCA']['tl_wem_map_item']['list']['operations']['geocode']);
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
        // if (\strlen(Input::get('tid') ?? '')) {
        if (Input::get('tid')) {
            $this->toggleVisibility(Input::get('tid'), ('1' === Input::get('state') ? true : false), (@func_get_arg(12) ?: null));
            $this->redirect($this->getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->User->hasAccess('tl_wem_map_item::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '1' : '0');

        if (!$row['published']) {
            $icon = 'invisible.gif';
        }

        return '<a href="'.$this->addToUrl($href).'" title="'.specialchars($title).'"'.$attributes.'">'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? '1' : '0').'"').'</a> ';
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
        if (!$this->User->hasAccess('tl_wem_map_item::published', 'alexf')) {
            $this->log('Not enough permissions to publish/unpublish agence item ID "'.$intId.'"', __METHOD__, TL_ERROR);
            $this->redirect('contao/main.php?act=error');
        }

        $objVersions = new Versions('tl_wem_map_item', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_wem_map_item']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_wem_map_item']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $this->import($callback[0]);
                    $blnVisible = $this->$callback[0]->$callback[1]($blnVisible, ($dc ?: $this));
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, ($dc ?: $this));
                }
            }
        }

        // Update the database
        $this->Database->prepare('UPDATE tl_wem_map_item SET tstamp='.time().", published='".($blnVisible ? '1' : '')."' WHERE id=?")
                       ->execute($intId)
        ;

        $objVersions->create();
        $this->log('A new version of record "tl_wem_map_item.id='.$intId.'" has been created'.$this->getParentEntries('tl_wem_map_item', $intId), __METHOD__, TL_GENERAL);
    }

    public function importButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, ?array $rootIds): string
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

    public function exportButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, ?array $rootIds): string
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

    public function geocodeAllButtonGlobalOperations(?string $href, string $label, string $title, string $class, string $attributes, string $table, ?array $rootIds): string
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
        DataContainer $dc
    ): string {
        $objMap = Map::findByPk(Input::get('id'));
        if (!$objMap
        || null === $objMap->geocodingProvider
        ) {
            return '';
        }
        $url = $this->addToUrl($href);
        $url = str_replace('&amp;id='.$objMap->id, '&amp;id='.$data['id'], $url);

        return sprintf('<a href="%s" title="%s" %s>%s</a> ', $url, StringUtil::specialchars($title), $attributes, Image::getHtml($icon, $label));
    }

    public function syncMapItemCategoryPivotTable($varValue, $dc)
    {
        $this->syncData(deserialize($varValue), 'tl_wem_map_item_category', $dc->id, 'pid', 'category');

        return $varValue;
    }
}
