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

namespace WEM\GeoDataBundle\DataContainer;

use Contao\DataContainer;
use WEM\GeoDataBundle\Model\Category;

class MapCategory extends CoreContainer
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems($row)
    {
        return $row['title'].($row['is_default'] ? ' ('.$GLOBALS['TL_LANG'][Category::getTable()]['is_default']['label'].')' : '');
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        // remove default tag on other categories of the same map
        if ((bool) $dc->activeRecord->default) {
            $db = \Contao\Database::getInstance();
            $db->query('
                UPDATE %s
                SET `default` = "0"
                WHERE `pid` = %s
                AND `id` != %s
                ',
            Category::getTable(),
            $dc->activeRecord->pid,
            $dc->activeRecord->id
            );
        } else {
            // check if another category is the default one for the map
            // if not, make this one the default's one, sorry not sorry
            $defaultCategory = Category::findItems(['pid' => $dc->activeRecord->pid, 'is_default' => '1'], 1);
            if (!$defaultCategory) {
                $objCategory = Category::findByPk($dc->id);
                $objCategory->is_default = 1;
                $objCategory->save();
            }
        }
    }
}
