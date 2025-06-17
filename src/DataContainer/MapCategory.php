<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\DataContainer;

use Contao\DataContainer;
use Contao\Model\Collection;
use Exception;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Model\Category;

class MapCategory extends CoreContainer
{
    /**
     * Design each row of the DCA.
     */
    public function listItems(array $row): string
    {
        return $row['title'] . ($row['is_default'] ? ' (' . $GLOBALS['TL_LANG'][Category::getTable()]['is_default']['label'] . ')' : '');
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        if (! $dc->id) {
            return;
        }

        // remove default tag on other categories of the same map
        if ((bool) $dc->activeRecord->is_default) {
            $this->Database
                ->prepare('UPDATE ' . Category::getTable() . '
                SET `is_default` = "0"
                WHERE `pid` = ?
                AND `id` != ?')
                ->execute($dc->activeRecord->pid, $dc->activeRecord->id)
            ;
        } else {
            // check if another category is the default one for the map if not, make this one

            // the default's one, sorry not sorry
            $defaultCategory = Category::findItems([
                'pid' => $dc->activeRecord->pid,
                'is_default' => '1',
            ], 1);
            if (! $defaultCategory instanceof Collection) {
                $objCategory = Category::findById($dc->id);
                $objCategory->is_default = 1;
                $objCategory->save();
            }
        }
    }

    public function ondeleteCallback(DataContainer $dc): void
    {
        if (! $dc->id) {
            return;
        }

        // if this category is the default one, you can't delete it
        if ((bool) $dc->activeRecord->is_default) {
            throw new Exception('You cannot delete the default category');
        }

        $objCategory = Category::findById($dc->id);
        if ($objCategory) {
            Util::deleteMapItemCategoryForCategory($objCategory);
        }
    }
}
