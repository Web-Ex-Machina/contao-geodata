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

namespace WEM\GeoDataBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemCategory;

class M202307181508_DefaultMapCategory extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->getSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_wem_map_category'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_wem_map_category');

        if (!isset($columns['is_default'])) {
            return false;
        }

        return $this->countItems() > 0;
    }

    public function run(): MigrationResult
    {
        $maps = $this->getMapsWithoutCategory();
        $i = 0;
        $arrIds = [];
        if ($maps) {
            while ($maps->next()) {
                $objMapCategory = new Category();
                $objMapCategory->tstamp = time();
                $objMapCategory->created_at = time();
                $objMapCategory->pid = $maps->id;
                $objMapCategory->is_default = '1';
                $objMapCategory->title = 'Default';
                $objMapCategory->markerConfig = serialize([]);
                $objMapCategory->save();

                $arrIds[] = $maps->id;
                ++$i;

                $mapItems = $this->getMapItemsWithoutCategory((int) $maps->id);
                if ($mapItems) {
                    while ($mapItems->next()) {
                        $this->assignCategoryToMapItem($mapItems->current(), $objMapCategory);
                    }
                }
            }
        }

        $maps = $this->getMapsWithoutDefaultCategory();
        if ($maps) {
            while ($maps->next()) {
                if (\in_array($maps->id, $arrIds, true)) {
                    continue;
                }
                $objMapCategory = new Category();
                $objMapCategory->tstamp = time();
                $objMapCategory->created_at = time();
                $objMapCategory->pid = $maps->id;
                $objMapCategory->is_default = '1';
                $objMapCategory->title = 'Default';
                $objMapCategory->markerConfig = serialize([]);
                $objMapCategory->save();

                $arrIds[] = $maps->id;
                ++$i;

                $mapItems = $this->getMapItemsWithoutCategory((int) $maps->id);
                if ($mapItems) {
                    while ($mapItems->next()) {
                        $this->assignCategoryToMapItem($mapItems->current(), $objMapCategory);
                    }
                }
            }
        }

        return $this->createResult(
            true,
            $i.' default categories created/updated.'
        );
    }

    private function getMapsWithoutDefaultCategory()
    {
        return Map::findItems([
            'where' => [
                sprintf('%s.id NOT IN (SELECT DISTINCT c.pid FROM %s c WHERE c.is_default = 1) AND %s.id NOT IN (SELECT DISTINCT c.pid FROM %s c)', Map::getTable(), Category::getTable(), Map::getTable(), Category::getTable()),
            ],
        ]);
    }

    private function getMapsWithoutCategory()
    {
        return Map::findItems([
            'where' => [
                sprintf('%s.id NOT IN (SELECT DISTINCT c.pid FROM %s c)', Map::getTable(), Category::getTable()),
            ],
        ]);
    }

    private function getMapItemsWithoutCategory(int $mapId)
    {
        return MapItem::findItems([
            'pid' => $mapId,
            'where' => [
                sprintf('%s.id NOT IN (SELECT DISTINCT %s.pid FROM %s)', MapItem::getTable(), MapItemCategory::getTable(), MapItemCategory::getTable()),
            ],
        ]);
    }

    private function assignCategoryToMapItem(MapItem $objMapItem, Category $objCategory): void
    {
        $objMapItemCategory = new MapItemCategory();
        $objMapItemCategory->tstamp = time();
        $objMapItemCategory->createdAt = time();
        $objMapItemCategory->pid = $objMapItem->id;
        $objMapItemCategory->category = $objCategory->id;
        $objMapItemCategory->save();

        $objMapItem->categories = serialize([$objCategory->id]);
        $objMapItem->save();
    }

    private function countItems(): int
    {
        $mapsWithoutCategory = $this->getMapsWithoutCategory();
        $mapsWithoutDefaultCategory = $this->getMapsWithoutDefaultCategory();

        if (!$mapsWithoutCategory && !$mapsWithoutDefaultCategory) {
            return 0;
        }

        return ($mapsWithoutCategory ? $mapsWithoutCategory->count() : 0) + ($mapsWithoutDefaultCategory ? $mapsWithoutDefaultCategory->count() : 0);
    }
}
