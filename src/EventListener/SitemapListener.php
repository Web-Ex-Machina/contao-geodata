<?php

declare(strict_types=1);

/**
 * Altrad Map Bundle for Contao Open Source CMS
 * Copyright (c) 2017-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-altrad-map-bundle
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-altrad-map-bundle/
 */

namespace WEM\GeoDataBundle\EventListener;

use Contao\CoreBundle\Event\SitemapEvent;
use Contao\PageModel;
use Contao\Model\Collection;
use WEM\GeoDataBundle\Model\Item;
use WEM\GeoDataBundle\Model\Map;

class SitemapListener
{
    public function __invoke(SitemapEvent $event): void
    {
        $maps = Map::findItems();
        if(!$maps){
            return;
        }

        $this->parseMaps($event, $maps);
    }

    /**
     * Parse all maps to add or not their itemps to the sitemap
     * @param  SitemapEvent $event 
     * @param  Collection       $maps  
     */
    protected function parseMaps(SitemapEvent $event,Collection $maps): void
    {
        while($maps->next()){
            $this->parseMap($event,$maps->current());
        }
    }

    /**
     * Parse a map to add or not its items to the sitemap
     * @param  SitemapEvent $event 
     * @param  Map          $map   
     */
    protected function parseMap(SitemapEvent $event,Map $map): void
    {
        if($map->doNotAddItemsToContaoSitemap
        || !$map->jumpTo
        ){
            return;
        }

        $items = Item::findItems(['pid'=>$map->id,'published'=>1]);
        if(!$items){
            return;
        }
        $this->parseItems($event, $map, $items);
    }

    /**
     * Parse all markers from a map
     * @param  SitemapEvent $event 
     * @param  Map          $map   
     * @param  Collection       $items           
     */
    protected function parseItems(SitemapEvent $event,Map $map, Collection $items): void
    {
        while($items->next()){
            $this->parseItem($event,$map, $items->current());
        }
    }

    /**
     * Add a single marker into the sitemap
     * @param  SitemapEvent $event 
     * @param  Map          $map   
     * @param  Item         $item  
     */
    protected function parseItem(SitemapEvent $event, Map $map, Item $item): void
    {

        $page = PageModel::findById($map->jumpTo);
        if(!$page){
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