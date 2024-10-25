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

use Contao\Controller;
use WEM\UtilsBundle\Model\Model as CoreModel;

/**
 * Reads and writes items.
 */
class MapItem extends CoreModel
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map_item';

    /**
     * Search fields.
     */
    protected static $arrSearchFields = ['title', 'teaser', 'attr_value_postal'];

    /**
     * Generic statements format.
     *
     * @param string $strField    [Column to format]
     * @param mixed  $varValue    [Value to use]
     * @param string $strOperator [Operator to use, default "="]
     */
    public static function formatStatement($strField, $varValue, $strOperator = '='): array
    {
        $arrColumns = [];
        $t = static::$strTable;

        switch ($strField) {
            case 'onlyWithCoords':
                $arrColumns[] = $t.".lat != '' AND ".$t.".lng != ''";
                break;

            case 'published':
                $timestamp = (new \DateTime())->getTimestamp();
                if (1 === (int) $varValue) {
                    $arrColumns[] = \sprintf("$t.published = 1
                        AND ($t.publishedAt = '' OR $t.publishedAt <= %s)
                        AND ($t.publishedUntil = '' OR $t.publishedUntil >= %s)", $timestamp, $timestamp
                    );
                } else {
                    $arrColumns[] = \sprintf("$t.published = 0
                        OR ($t.published = 1 AND $t.publishedAt >= %s)
                        OR ($t.published = 1 AND $t.publishedUntil <= %s)", $timestamp, $timestamp
                    );
                }
                break;

            case 'category':
                if (!\is_array($varValue)) {
                    $varValue = [$varValue];
                }

                $arrColumns[] = \sprintf("{$t}.id IN (
                    SELECT mic.pid
                    FROM %s mic
                    INNER JOIN %s mc ON mc.id = mic.category
                    WHERE mc.id IN ('%s')
                    OR mc.title IN ('%s')
                    OR LOWER(REPLACE(REPLACE(mc.title,' ','_'),'.','_')) IN ('%s')
                )",
                    MapItemCategory::getTable(),
                    Category::getTable(),
                    implode("','", $varValue),
                    implode("','", $varValue),
                    implode("','", $varValue),
                );
                break;
            case 'categories':
                if (!\is_array($varValue)) {
                    $varValue = [$varValue];
                }
                $arrColumns[] = \sprintf("$t.id IN (
                    SELECT mic.pid
                    FROM %s mic
                    WHERE mic.category IN ('%s')
                )",
                    MapItemCategory::getTable(),
                    implode("','", $varValue),
                );
                break;
            default:
                // HOOK: add custom logic
                if (isset($GLOBALS['TL_HOOKS']['WEMGEODATAMAPITEMFORMATSTATEMENT']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATAMAPITEMFORMATSTATEMENT'])) {
                    foreach ($GLOBALS['TL_HOOKS']['WEMGEODATAMAPITEMFORMATSTATEMENT'] as $callback) {
                        $arrColumnsTemp = Controller::importStatic($callback[0])->{$callback[1]}($strField, $varValue, $strOperator, $arrColumns, self::class);
                        if (null !== $arrColumnsTemp) {
                            $arrColumns = $arrColumnsTemp;
                        } else {
                            $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                        }
                    }
                } else {
                    $arrColumns = array_merge($arrColumns, parent::formatStatement($strField, $varValue, $strOperator));
                }
        }

        return $arrColumns;
    }

    /**
     * Format Search statement.
     *
     * @param string $strField
     * @param string $varValue [Value to use]
     */
    public static function formatSearchStatement($strField, $varValue): string
    {
        $t = static::$strTable;

        switch ($strField) {
            case 'attr_value_postal':
                return $t."id IN(
                    SELECT tl_wem_map_item_attribute_value.pid
                    FROM tl_wem_map_item_attribute_value
                    WHERE tl_wem_map_item_attribute_value.attribute = 'postal'
                    AND tl_wem_map_item_attribute_value.value REGEXP '".$varValue."')";
            default:
                return parent::formatSearchStatement($strField, $varValue);
        }
    }

    public function isPublishedForTimestamp(?int $timestamp = null): bool
    {
        $timestamp ?? (new \DateTime())->getTimestamp();

        return $this->published
            && (empty($this->publishedAt) || (!empty($this->publishedAt) && (int) $this->publishedAt < $timestamp))
            && (empty($this->publishedUntil) || (!empty($this->publishedUntil) && (int) $this->publishedUntil > $timestamp));
    }
}
