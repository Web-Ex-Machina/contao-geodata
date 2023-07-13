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

namespace WEM\GeoDataBundle\Model;

use Contao\Model;

/**
 * Reads and writes items.
 */
class Category extends Model
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map_category';

    /**
     * Find items, depends on the arguments.
     *
     * @param array
     * @param int
     * @param int
     * @param array
     *
     * @return Collection
     */
    public static function findItems($arrConfig = [], $intLimit = 0, $intOffset = 0, $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if ($intLimit > 0) {
            $arrOptions['limit'] = $intLimit;
        }

        if ($intOffset > 0) {
            $arrOptions['offset'] = $intOffset;
        }

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = "$t.title ASC";
        }

        if (empty($arrColumns)) {
            return static::findAll($arrOptions);
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    /**
     * Count items, depends on the arguments.
     *
     * @param array
     * @param array
     *
     * @return int
     */
    public static function countItems($arrConfig = [], $arrOptions = [])
    {
        $t = static::$strTable;
        $arrColumns = static::formatColumns($arrConfig);

        if (empty($arrColumns)) {
            return static::countAll($arrOptions);
        }

        return static::countBy($arrColumns, null, $arrOptions);
    }

    /**
     * Format ItemModel columns.
     *
     * @param [Array] $arrConfig [Configuration to format]
     *
     * @return [Array] [The Model columns]
     */
    public static function formatColumns($arrConfig)
    {
        $t = static::$strTable;
        $arrColumns = [];

        if ($arrConfig['pid']) {
            $arrColumns[] = "$t.pid = ".$arrConfig['pid'];
        }

        if ($arrConfig['not']) {
            $arrColumns[] = $arrConfig['not'];
        }

        return $arrColumns;
    }
}
