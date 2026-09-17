<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Service;

use Contao\Config;
use Exception;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide Leaflet utilities functions
 */
class Leaflet extends Provider
{
	/**
     * Default Leaflet Map Config.
     *
     * @return [Array]
     */
    public static function getDefaultConfig(): array
    {
        return [
            'provider' => 'leaflet',
            'zoom' => 13,
            'tileLayer_url' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'tileLayer_attribution' => 'Map data © <a href="https://openstreetmap.org">OpenStreetMap</a> contributors',
            'tileLayer_minZoom' => 0,
            'tileLayer_maxZoom' => 18,
            'tileLayer_id' => '',
            'tileLayer_accessToken' => '',
        ];
    }
    	public function loadMapAssets(): void
	{
		$v = Config::get('wem_geodata_assets_version') ?: WEM_GEODATA_COMBINER_VERSION;

		$GLOBALS['TL_CSS'][] = 'https://unpkg.com/leaflet@latest/dist/leaflet.css';
        $GLOBALS['TL_CSS'][] = 'https://unpkg.com/leaflet.markercluster@latest/dist/MarkerCluster.css';
        $GLOBALS['TL_CSS'][] = 'https://unpkg.com/leaflet.markercluster@latest/dist/MarkerCluster.Default.css';
        $GLOBALS['TL_CSS'][] = 'https://unpkg.com/leaflet-gesture-handling@latest/dist/leaflet-gesture-handling.min.css';
        $GLOBALS['TL_CSS'][] = 'bundles/wemgeodata/css/leaflet.css|' . $v;

        $GLOBALS['TL_JAVASCRIPT'][] = 'https://unpkg.com/leaflet@latest/dist/leaflet.js';
        $GLOBALS['TL_JAVASCRIPT'][] = 'https://unpkg.com/leaflet.markercluster@latest/dist/leaflet.markercluster.js';
        $GLOBALS['TL_JAVASCRIPT'][] = 'https://unpkg.com/leaflet-gesture-handling@latest/dist/leaflet-gesture-handling.min.js';
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/js/default.js|' . $v;
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/js/leaflet.js|' . $v;
	}
}