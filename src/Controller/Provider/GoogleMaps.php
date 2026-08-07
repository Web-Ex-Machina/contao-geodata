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
use Exception;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Class GoogleMaps.
 */
class GoogleMaps extends Controller
{
    /**
     * Google Map Geocoding URL to request (sprintf pattern).
     */
    protected static string $strGeocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Return the coords lat/lng for a given address.
     *
     * @param string|MapItem $varAddress Address to geocode
     * @param Map            $objMap     Map Model
     * @param int|null       $intResults Number of API results wanted
     *
     * @return array|null [Address Components]
     */
    public function geocoder(
        $varAddress,
        Map $objMap,
        int|null $intResults = 1
    ): array|null { // removed static because using service is not possible with
        // Feature removed in 2.0
        throw new Exception(\sprintf(
            $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['featureDeletedIn'],
            'Geocoding by Google',
            '2.0'
        ));
    }
}
