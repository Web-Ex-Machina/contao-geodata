<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\EventListener\DataContainer;

use Contao\BackendUser;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Image;
use Contao\Input;
use Contao\Model\Collection;
use Contao\PageModel;
use Contao\StringUtil;
use Contao\System;
use Contao\Versions;
use Exception;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\UtilsBundle\Classes\CountriesUtil;

class MapItemContainer extends CoreContainer
{
    #[AsCallback(table: 'tl_wem_map_item', target: 'config.onload')]
    public function checkIfGeocodeExists(): void
    {
        $objMap = Map::findById(Input::get('id'));

        if (!$objMap || $objMap->geocodingProvider === '') {
            unset($GLOBALS['TL_DCA']['tl_wem_map_item']['list']['global_operations']['geocodeAll'], $GLOBALS['TL_DCA']['tl_wem_map_item']['list']['operations']['geocode']);
        }
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'list.sorting.child_record')]
    public function listItems(array $arrRow): string
    {
        $arrCountries = CountriesUtil::getCountries();
        $strColor = ! $arrRow['lat'] || ! $arrRow['lng'] ? '#ff0000' : '#333';

        $strRow = \sprintf(
            '<span style="color:%s">%s</span> <span style="color:#888">[%s - %s]</span>',
            $strColor,
            $arrRow['title'],
            $arrRow['city'],
            $arrCountries[$arrRow['country']]
        );

        return $strRow . '<div class="ajax-results"></div>';
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'list.global_operations.geocodeAll.button')]
    public function geocodeAllButtonGlobalOperations(
        string|null $href,
        string $label,
        string $title,
        string|null $class,
        string $attributes,
        string $table,
        array|null $rootIds
    ): string {
        $objMap = Map::findById(Input::get('id'));
        if (!$objMap || $objMap->geocodingProvider === null) {
            return '';
        }

        $url = $this->addToUrl($href);

        return \sprintf(
            '<a href="%s" title="%s" class="%s" %s>%s</a>',
            $url,
            StringUtil::specialchars($title),
            $class ?: '',
            $attributes,
            $label
        );
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'list.global_operations.import.button')]
    public function importButtonGlobalOperations(
        string|null $href,
        string $label,
        string $title,
        string|null $class,
        string $attributes,
        string $table,
        array|null $rootIds
    ): string 
    {
        $objMap = Map::findById(Input::get('id'));
        if (!$objMap || $objMap->excelPattern === null || empty(StringUtil::deserialize($objMap->excelPattern))) {
            return '';
        }

        $url = $this->addToUrl($href);

        return \sprintf(
            '<a href="%s" title="%s" class="%s" %s>%s</a>',
            $url,
            StringUtil::specialchars($title),
            $class ?: '',
            $attributes,
            $label
        );
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'list.global_operations.export.button')]
    public function exportButtonGlobalOperations(
        string|null $href,
        string $label,
        string $title,
        string|null $class,
        string $attributes,
        string $table,
        array|null $rootIds
    ): string  
    {
        $objMap = Map::findById(Input::get('id'));
        if (! $objMap || $objMap->excelPattern === null || empty(StringUtil::deserialize($objMap->excelPattern))) {
            return '';
        }

        $url = $this->addToUrl($href);

        return \sprintf(
            '<a href="%s" title="%s" class="%s" %s>%s</a>',
            $url,
            StringUtil::specialchars($title),
            $class ?: '',
            $attributes,
            $label
        );
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'list.operations.geocode.button')]
    public function geocodeButtonOperations(DataContainerOperation $operation): void 
    {
        $objMap = Map::findById(Input::get('id'));
        if (!$objMap || $objMap->geocodingProvider === null) {
            $operation->hide();
        }

        $url = $this->addToUrl('key=geocode');
        $url = str_replace('&amp;id=' . $objMap->id, '&amp;id=' . Input::get('id'), $url);
        $operation->setUrl($url);
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'fields.alias.save')]
    public function generateAlias($varValue, DataContainer $dc): string 
    {
        $autoAlias = false;

        // Generate alias if there is none
        if ($varValue === '') {
            $autoAlias = true;
            $slugOptions = [];

            // Read the slug options from the associated page
            if (
                null !== ($objMap = Map::findById(
                    $dc->activeRecord->pid
                )) && null !== ($objPage = PageModel::findWithDetails(
                    $objMap->jumpTo
                ))
            ) {
                $slugOptions = $objPage->getSlugOptions();
            }

            $varValue = System::getContainer()->get('contao.slug.generator')->generate(
                StringUtil::prepareSlug($dc->activeRecord->title),
                $slugOptions
            );

            // Prefix numeric aliases (see #1598)
            if (is_numeric($varValue)) {
                $varValue = 'id-' . $varValue;
            }
        }

        $objAlias = $this->Database->prepare('SELECT id FROM tl_wem_map_item WHERE alias=? AND id!=?')
            ->execute($varValue, $dc->id)
        ;

        // Check whether the news alias exists
        if ($objAlias->numRows) {
            if (! $autoAlias) {
                throw new Exception(\sprintf($GLOBALS['TL_LANG']['ERR']['aliasExists'], $varValue));
            }

            $varValue .= '-' . $dc->id;
        }

        return $varValue;
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'fields.categories.options')]
    public function getMapCategories(DataContainer|null $dc = null): array 
    {
        $arrData = [];

        if ($dc->activeRecord && $dc->activeRecord->pid) {
            $objCategories = $this->Database->prepare(
                'SELECT id, title FROM tl_wem_map_category WHERE pid = ? ORDER BY createdAt ASC'
            )
                ->execute($dc->activeRecord->pid)
            ;

            if (! $objCategories) {
                return [];
            }

            while ($objCategories->next()) {
                $arrData[$objCategories->id] = $objCategories->title;
            }
        }

        return $arrData;
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'fields.categories.load')]
    public function assignDefaultCategoryIfNew($value, DataContainer $dc): string
    {
        if (! $dc->id || ! $dc->activeRecord->categories) {
            $objDefaultCategory = Category::findItems(['pid' => $dc->activeRecord->pid,
                'is_default' => '1']);
            if ($objDefaultCategory instanceof Collection) {
                return serialize([$objDefaultCategory->id]);
            }
        }

        return $value;
    }

    #[AsCallback(table: 'tl_wem_map_item', target: 'fields.categories.save')]
    public function syncMapItemCategoryPivotTable($varValue, $dc)
    {
        $this->syncData(StringUtil::deserialize($varValue), 'tl_wem_map_item_category', $dc->id, 'pid', 'category');

        return $varValue;
    }
}
