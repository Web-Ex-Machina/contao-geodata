<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\Model\Collection;
use Doctrine\DBAL\Connection;
use Exception;
use LengthException;
use WEM\GeoDataBundle\Model\Map;
use WEM\UtilsBundle\Classes\Encryption;

class M202410251433_EncodeMapProviderGmapKey extends AbstractMigration
{
    private Connection $connection;

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
        if (

            ! $schemaManager->tablesExist(
                ['tl_wem_map']
            )

        ) {
            return false;
        }

        $columns = $schemaManager->listTableColumns('tl_wem_map');

        if (! isset($columns[strtolower('mapProviderGmapKey')])) {
            return false;
        }

        $maps = $this->getItems();
        $i = 0;
        if ($maps instanceof Collection) {
            while ($maps->next()) {
                /** @var Map $objMap */
                $objMap = $maps->current();
                // if decrypt throws error, it means it wasn't encrypted
                if (

                    $this->isValueEncrypted(
                        $objMap->mapProviderGmapKey
                    )

                ) {
                    continue;
                }

                ++$i;
            }
        }

        return $i > 0;
    }

    public function run(): MigrationResult
    {
        $maps = $this->getItems();
        $i = 0;
        if ($maps instanceof Collection) {
            while ($maps->next()) {
                /** @var Map $objMap */
                $objMap = $maps->current();
                // if decrypt throws error, it means it wasn't encrypted
                if (

                    $this->isValueEncrypted(
                        $objMap->mapProviderGmapKey
                    )

                ) {
                    continue;
                }

                $objMap->mapProviderGmapKey = $this->encryption->encrypt_b64(
                    $objMap->mapProviderGmapKey
                );
                $objMap->save();

                ++$i;
            }
        }

        return $this->createResult(
            true,
            $i . ' map(s) updated.'
        );
    }

    protected function isValueEncrypted(?string $val): bool
    {
        try {
            $this->encryption->decrypt_b64($val);

            return true;
        } catch (LengthException $lengthException) {
            return false;
        }
    }

    private function getItems(): ?Collection
    {
        try {
            return Map::findItems([
                'where' => [
                    \sprintf('LENGTH(%s.mapProviderGmapKey) > 0', Map::getTable()),
                ],
            ]);
        } catch (Exception $exception) {
            return null;
        }
    }
}
