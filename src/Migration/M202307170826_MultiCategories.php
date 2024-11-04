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

namespace WEM\GeoDataBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemCategory;

class M202307170826_MultiCategories extends AbstractMigration
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
        $schemaManager = $this->connection->createSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_wem_map_item', 'tl_wem_map_item_category'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_wem_map_item');

        if (!isset($columns['categories'])) {
            return false;
        }

        return $this->countItems() > 0;
    }

    public function run(): MigrationResult
    {
        $mapItems = $this->getItems();
        $i = 0;
        if ($mapItems) {
            while ($mapItems->next()) {
                $objMapItem = $mapItems->current();
                $objMapItem->categories = serialize([$objMapItem->category]);
                $objMapItem->save();

                $objMapItemCategory = new MapItemCategory();
                $objMapItemCategory->tstamp = time();
                $objMapItemCategory->created_at = time();
                $objMapItemCategory->pid = $objMapItem->id;
                $objMapItemCategory->category = $objMapItem->category;
                $objMapItemCategory->save();
                ++$i;
            }
        }

        return $this->createResult(
            true,
            $i.' location(s) updated.'
        );
    }

    private function getItems()
    {
        try {
            return MapItem::findItems([
                'where' => [
                    \sprintf('LENGTH(%s.category) > 0 AND %s.category != 0 AND %s.id NOT IN (SELECT DISTINCT %s.pid FROM %s)', MapItem::getTable(), MapItem::getTable(), MapItem::getTable(), MapItemCategory::getTable(), MapItemCategory::getTable()),
                ],
            ]);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function countItems()
    {
        $items = $this->getItems();
        if (!$items) {
            return 0;
        }

        return $items->count();
    }
}
