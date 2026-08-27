<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Classes;

use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use Exception;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemCategory;

/**
 * Provide utilities function to Locations Extension.
 */
class Util
{
    /**
     * Format string value for use in filters (for better readability in URL).
     *
     * @param string $value The raw value
     *
     * @return string the formatted value
     */
    public static function formatStringValueForFilters(
        string $value
    ): string {
        return str_replace([' ', '.'], '_', mb_strtolower($value, 'UTF-8'));
    }

    /**
     * Delete MapItemCategory rows for a Category.
     *
     * @param Category $objItem The Category
     */
    public static function deleteMapItemCategoryForCategory(
        Category $objItem
    ): void {
        // remove links item <-> category
        $mapItemCategories = MapItemCategory::findItems(['category' => $objItem->id]);
        if ($mapItemCategories instanceof Collection) {
            while ($mapItemCategories->next()) {
                $mapItemCategories->current()
                    ->delete()
                ;
            }
        }
    }
}
