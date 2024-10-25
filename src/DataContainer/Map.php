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
use Contao\Message;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map as ModelMap;

class Map extends CoreContainer
{
    public function onloadCallback(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        // check if another category is the default one for the map
        // if not, show an error
        $defaultCategory = Category::findItems(['pid' => $dc->id, 'is_default' => '1'], 1);
        if (!$defaultCategory) {
            Message::addError('No default category on this map. Add one !');
        }
    }

    public function onsubmitCallback(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        // check if another category is the default one for the map
        // if not, make this one the default's one, sorry not sorry
        $defaultCategory = Category::findItems(['pid' => $dc->id, 'is_default' => '1'], 1);
        if (!$defaultCategory) {
            $newDefaultCategory = Category::findItems(['pid' => $dc->id], 1);
            if (!$newDefaultCategory) {
                $newDefaultCategory = new Category();
                $newDefaultCategory->createdAt = time();
                $newDefaultCategory->tstamp = time();
                $newDefaultCategory->title = 'Default';
                $newDefaultCategory->markerConfig = serialize([]);
                $newDefaultCategory->pid = $dc->id;
            }

            $newDefaultCategory->is_default = 1;
            $newDefaultCategory->save();
        }
    }

    /**
     * Generate the default map config array.
     */
    public function getDefaultMapConfig(array $varValue, $objDc): array
    {
        if (!$varValue) {
            switch ($objDc->activeRecord->mapProvider) {
                case ModelMap::MAP_PROVIDER_LEAFLET:
                    $arrConfig = \WEM\GeoDataBundle\Controller\Provider\Leaflet::getDefaultConfig();
                    break;

                default:
                    $arrConfig = [];
            }

            foreach ($arrConfig as $strKey => $strValue) {
                $varValue[] = ['key' => $strKey, 'value' => $strValue];
            }
        }

        return $varValue;
    }

    /**
     * Generate the default Excel pattern.
     */
    public function generateExcelPattern(array $varValue): array
    {
        if (!$varValue) {
            $varValue = [
                ['key' => 'title', 'value' => 'A'], ['key' => 'lat', 'value' => 'B'], ['key' => 'lng', 'value' => 'C'], ['key' => 'street', 'value' => 'D'], ['key' => 'postal', 'value' => 'E'], ['key' => 'city', 'value' => 'F'], ['key' => 'region', 'value' => 'G'], ['key' => 'country', 'value' => 'H'], ['key' => 'phone', 'value' => 'I'], ['key' => 'email', 'value' => 'J'], ['key' => 'website', 'value' => 'K'],
            ];
        }

        return $varValue;
    }
}
