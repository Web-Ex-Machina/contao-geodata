<?php

declare(strict_types=1);

/**
 * Altrad Map Bundle for Contao Open Source CMS
 * Copyright (c) 2017-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-altrad-map-bundle
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-altrad-map-bundle/
 */

namespace WEM\GeoDataBundle\Model;

use WEM\UtilsBundle\Model\Model as CoreModel;

/**
 * Reads and writes items.
 */
class Item extends CoreModel
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_item';

    /**
     * Search fields
     *
     * @var array
     */
    protected static $arrSearchFields = ['title', 'teaser', 'attr_value_postal'];

    /**
     * Generic statements format.
     *
     * @param string $strField    [Column to format]
     * @param mixed  $varValue    [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     *
     * @return array
     */
    public static function formatStatement($strField, $varValue, $strOperator = '=')
    {
        $arrColumns = [];
        $t = static::$strTable;

        switch ($strField) {
            case 'onlyWithCoords':
                $arrColumns[] = "$t.lat != '' AND $t.lng != ''";
            break;

            default:
                $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
        }

        return $arrColumns;
    }

    /**
     * Format Search statement.
     *
     * @param string $varValue [Value to use]
     *
     * @return string
     */
    public static function formatSearchStatement($strField, $varValue)
    {
        $t = static::$strTable;

        switch ($strField) {
            case 'attr_value_postal':
                return "$t.id IN(
                    SELECT tl_wem_item_attr_value.pid
                    FROM tl_wem_item_attr_value
                    WHERE tl_wem_item_attr_value.attribute = 'postal'
                    AND tl_wem_item_attr_value.value REGEXP '$varValue'
                )";
                break;
            default:
                return parent::formatSearchStatement($strField, $varValue);
        }
    }
}
