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

namespace WEM\GeoDataBundle\DataContainer;

use Contao\DataContainer;
use WEM\GeoDataBundle\Classes\Util;

class MapItemCategory extends CoreContainer
{
    public function ondeleteCallback(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $mapItem = MapItem::findByPk($dc->activeRecord->pid);
        if ($mapItem) {
            Util::refreshMapItemCategoriesField($mapItem, [$dc->activeRecord->category]);
        }
    }
}
