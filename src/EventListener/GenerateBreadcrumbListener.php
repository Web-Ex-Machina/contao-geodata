<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\EventListener;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Database;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\ModuleModel;
use Contao\PageModel;
use WEM\GeoDataBundle\Model\MapItem;

class GenerateBreadcrumbListener
{
    public function __invoke(array $items, Module $module): array
    {
        // Modify $items …
        $lastItem = $items[\count($items) - 1];
        if (! ($lastItem['data']['id'] ?? false)) {
            return $items;
        }

        $query = \sprintf(
            '
            SELECT m.id
            FROM %s p
            INNER JOIN %s a ON a.pid = p.id
            INNER JOIN %s c ON c.pid = a.id AND c.ptable = "%s" AND c.type = "module"
            INNER JOIN %s m ON c.module = m.id
            AND m.type = "wem_geodata_reader"
            WHERE p.id = %s
        ',
            PageModel::getTable(),
            ArticleModel::getTable(),
            ContentModel::getTable(),
            ArticleModel::getTable(),
            ModuleModel::getTable(),
            $lastItem['data']['id']
        );

        $db = Database::getInstance();
        $res = $db->query($query);

        if ($res->count() >= 1) {
            $objMapItem = MapItem::findItems(['alias' => Input::get('auto_item')]);
            if ($objMapItem instanceof Collection) {
                $items[\count($items) - 1]['title'] = $objMapItem->title;
                $items[\count($items) - 1]['link'] = $objMapItem->title;
            }
        }

        return $items;
    }
}
