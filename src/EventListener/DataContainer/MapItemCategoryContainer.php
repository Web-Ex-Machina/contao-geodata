<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use WEM\GeoDataBundle\Classes\Util;

class MapItemCategoryContainer extends CoreContainer
{
    #[AsCallback(table: 'tl_wem_map_item_category', target: 'config.ondelete')]
    public function ondeleteCallback(DataContainer $dc): void
    {
        if (! $dc->id) {
            return;
        }

        $mapItem = MapItem::findById($dc->activeRecord->pid);
        if ($mapItem) {
            Util::refreshMapItemCategoriesField($mapItem, [$dc->activeRecord->category]);
        }
    }
}
