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
     * Calculates the great-circle distance between two points, with the Vincenty formula.
     *
     * @param float $latitudeFrom  Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo    Latitude of target point in [deg decimal]
     * @param float $longitudeTo   Longitude of target point in [deg decimal]
     * @param float $earthRadius   Mean earth radius in [m]
     *
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function vincentyGreatCircleDistance(
        float $latitudeFrom,
        float $longitudeFrom,
        float $latitudeTo,
        float $longitudeTo,
        float $earthRadius = 6371000
    ): float {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = (cos($latTo) * sin($lonDelta)) ** 2 +
        (cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta)) ** 2;
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
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
