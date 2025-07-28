<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Controller\Provider;

use Contao\Controller;

/**
 * Provide Leaflet utilities functions to Locations Extension.
 */
class Leaflet extends Controller
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
}
