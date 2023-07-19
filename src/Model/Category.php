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

namespace WEM\GeoDataBundle\Model;

use WEM\UtilsBundle\Model\Model as CoreModel;

/**
 * Reads and writes items.
 */
class Category extends CoreModel
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map_category';
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
            case 'pid':
                if(!is_array($varValue)){
                    $varValue = [$varValue];
                }
                $arrColumns[] = sprintf("$t.pid IN ('%s')",
                implode("','",$varValue),
                );
            break;
            default:
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
        }

        return $arrColumns;
    }
}
