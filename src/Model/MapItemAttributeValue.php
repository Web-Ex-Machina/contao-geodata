<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\Model;

use Contao\Model;

/**
 * Reads and writes items.
 */
class MapItemAttributeValue extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map_item_attribute_value';

    /**
     * Find items, depends on the arguments.
     *
     * @param array
     * @param int
     * @param int
     * @param array
     */
    public static function findItems(
        array $arrConfig = [], int $intLimit = 0,
        int $intOffset = 0, array $arrOptions = []
    ): ?Collection {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = $t.'.createdAt ASC';
        }

        if (empty($arrColumns)) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count items, depends on the arguments.
     */
    public static function countItems(array $arrConfig = [], array $arrOptions = []): int
    {
        $t = static::$strTable; // TODO : useless ?
        $arrColumns = static::formatColumns($arrConfig);

        if (empty($arrColumns)) {
            return static::countAll($arrOptions); // TODO : useless $arrOptions ?
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Format ItemModel columns.
     *
     * @param array $arrConfig Configuration to format
     *
     * @return array The Model columns
     */
    public static function formatColumns(array $arrConfig): array
    {
        $t = static::$strTable;
        $arrColumns = [];

        if ($arrConfig['pid']) {
            $arrColumns[] = $t.'.pid = '.$arrConfig['pid'];
        }

        if ($arrConfig['attribute']) {
            $arrColumns[] = $t.".attribute = '".$arrConfig['attribute']."'";
        }

        if ($arrConfig['value']) {
            $arrColumns[] = $t.".value = '".$arrConfig['value']."'";
        }

        if ($arrConfig['not']) {
            $arrColumns[] = $arrConfig['not'];
        }

        return $arrColumns;
    }
}
