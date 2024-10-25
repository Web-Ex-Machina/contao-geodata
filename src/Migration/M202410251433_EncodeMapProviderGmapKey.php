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
use WEM\GeoDataBundle\Model\Map;
use WEM\UtilsBundle\Classes\Encryption;

class M202410251433_EncodeMapProviderGmapKey extends AbstractMigration
{
    /**
     * @var Connection
     */
    private $connection;
    private Encryption $encryption;

    public function __construct(Connection $connection, Encryption $encryption)
    {
        $this->connection = $connection;
        $this->encryption = $encryption;
    }

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        // If the database table itself does not exist we should do nothing
        if (!$schemaManager->tablesExist(['tl_wem_map'])) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_wem_map');

        if (!isset($columns['mapProviderGmapKey'])) {
            return false;
        }

        return $this->countItems() > 0;
    }

    public function run(): MigrationResult
    {
        $maps = $this->getItems();
        $i = 0;
        if ($maps) {
            while ($maps->next()) {
                /** @var Map */
                $objMap = $maps->current();
                // if decrypt throws error, it means it wasn't encrypted
                try {
                    $decodedMapProviderGmapKey = $this->encryption->decrypt($objMap->mapProviderGmapKey);
                    continue;
                } catch (\LengthException $e) {
                    $objMap->mapProviderGmapKey = $this->encryption->encrypt($objMap->mapProviderGmapKey);
                    $objMap->save();
                }

                ++$i;
            }
        }

        return $this->createResult(
            true,
            $i.' map(s) updated.'
        );
    }

    private function getItems()
    {
        try {
            return Map::findItems([
                'where' => [
                    \sprintf('LENGTH(%s.mapProviderGmapKey) > 0', Map::getTable()),
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
