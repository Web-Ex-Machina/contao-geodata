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
use Contao\Message;
use Contao\Model\Collection;
use WEM\GeoDataBundle\Controller\Provider\Leaflet;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map as ModelMap;

class MapContainer extends CoreContainer
{
    #[AsCallback(table: 'tl_wem_map', target: 'config.onload')]
    public function onloadCallback(DataContainer $dc): void
    {
        if (! $dc->id) {
            return;
        }

        // check if another category is the default one for the map if not, show an error
        $defaultCategory = Category::findItems(
            [
                'pid' => $dc->id,
                'is_default' => '1',
            ],
            1
        );
        if (! $defaultCategory instanceof Collection) {
            Message::addError('No default category on this map. Add one !');
        }
    }

    #[AsCallback(table: 'tl_wem_map', target: 'config.onsubmit')]
    public function onsubmitCallback(DataContainer $dc): void
    {
        if (! $dc->id) {
            return;
        }

        // check if another category is the default one for the map if not, make this one

        // the default's one, sorry not sorry
        $defaultCategory = Category::findItems([
            'pid' => $dc->id,
            'is_default' => '1',
        ], 1);
        if (! $defaultCategory instanceof Collection) {
            $newDefaultCategory = Category::findItems(['pid' => $dc->id], 1);
            if (! $newDefaultCategory instanceof Collection) {
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

    #[AsCallback(table: 'tl_wem_map', target: 'fields.mapConfig.load')]
    public function getDefaultMapConfig(
        $varValue,
        DataContainer $objDc
    ) {
        if (! $varValue) {
            switch ($objDc->activeRecord->mapProvider) {
                case ModelMap::MAP_PROVIDER_LEAFLET:
                    $arrConfig = Leaflet::getDefaultConfig();
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

    #[AsCallback(table: 'tl_wem_map', target: 'fields.excelPattern.load')]
    public function generateExcelPattern($varValue)
    {
        if (! $varValue) {
            return [
                ['key' => 'title', 'value' => 'A'], 
                ['key' => 'lat', 'value' => 'B'], 
                ['key' => 'lng', 'value' => 'C'], 
                ['key' => 'street', 'value' => 'D'], 
                ['key' => 'postal', 'value' => 'E'], 
                ['key' => 'city', 'value' => 'F'], 
                ['key' => 'region', 'value' => 'G'], 
                ['key' => 'country', 'value' => 'H'], 
                ['key' => 'phone', 'value' => 'I'], 
                ['key' => 'email', 'value' => 'J'], 
                ['key' => 'website', 'value' => 'K'],
            ];
        }

        return $varValue;
    }
}
