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

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\Model\Collection;
use Contao\PageModel;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

class SitemapListener
{
    protected int $currentTimestamp;

    public function __invoke(SitemapEvent $event): void
    {
        $maps = Map::findItems();
        if (!$maps) {
            return;
        }

        $this->currentTimestamp = (new \DateTime())->getTimestamp();

        $this->parseMaps($event, $maps);
    }

    /**
     * Parse all maps to add or not their itemps to the sitemap.
     */
    protected function parseMaps(SitemapEvent $event, Collection $maps): void
    {
        while ($maps->next()) {
            $this->parseMap($event, $maps->current()); // TODO : Expected parameter of type '\WEM\GeoDataBundle\Model\Map', '\Contao\Model' provided
        }
    }

    /**
     * Parse a map to add or not its items to the sitemap.
     */
    protected function parseMap(SitemapEvent $event, Map $map): void
    {
        if ($map->doNotAddItemsToContaoSitemap
        || !$map->jumpTo
        ) {
            return;
        }

        $items = MapItem::findItems(['pid' => $map->id, 'published' => 1]);
        if (!$items) {
            return;
        }

        $this->parseItems($event, $map, $items);
    }

    /**
     * Parse all markers from a map.
     */
    protected function parseItems(SitemapEvent $event, Map $map, Collection $items): void
    {
        while ($items->next()) {
            $this->parseItem($event, $map, $items->current()); // TODO : Expected parameter of type '\WEM\GeoDataBundle\Model\Map', '\Contao\Model' provided
        }
    }

    /**
     * Add a single marker into the sitemap.
     */
    protected function parseItem(SitemapEvent $event, Map $map, MapItem $item): void
    {
        if (!$item->isPublishedForTimestamp($this->currentTimestamp)) {
            return;
        }

        $page = PageModel::findById($map->jumpTo);
        if (!$page) {
            return;
        }

        $sitemap = $event->getDocument();
        $urlSet = $sitemap->childNodes[0];

        $loc = $sitemap->createElement('loc', $page->getAbsoluteUrl('/'.$item->alias));
        $urlEl = $sitemap->createElement('url');
        $urlEl->appendChild($loc);

        $urlSet->appendChild($urlEl);
    }
}
