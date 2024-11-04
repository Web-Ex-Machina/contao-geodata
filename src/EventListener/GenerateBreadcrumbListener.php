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

namespace WEM\GeoDataBundle\EventListener;

use Contao\ArticleModel;
use Contao\ContentModel;
use Contao\Input;
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
        if(!($lastItem['data']['id'] ?? false)){
            return $items;
        }

        $query = \sprintf('
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

        $db = \Contao\Database::getInstance();
        $res = $db->query($query);

        if ($res->count() >= 1) {
            $objMapItem = MapItem::findItems(['alias' => Input::get('auto_item')]);
            if ($objMapItem) {
                $items[\count($items) - 1]['title'] = $objMapItem->title;
                $items[\count($items) - 1]['link'] = $objMapItem->title;
            }
        }

        return $items;
    }
}
