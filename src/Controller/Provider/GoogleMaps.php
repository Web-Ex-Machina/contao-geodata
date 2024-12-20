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

namespace WEM\GeoDataBundle\Controller\Provider;

use Contao\Controller;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\Encryption;

/**
 * Class GoogleMaps.
 */
class GoogleMaps extends Controller
{
    /**
     * Google Map Geocoding URL to request (sprintf pattern).
     */
    protected static string $strGeocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';

    private Encryption $encryption;

    protected function __construct(Encryption $encryption)
    {
        parent::__construct();
        $this->encryption = $encryption;
    }

    /**
     * Return the coords lat/lng for a given address.
     *
     * @param string|MapItem $varAddress Address to geocode
     * @param Map            $objMap     Map Model
     * @param int|null       $intResults Number of API results wanted
     *
     * @throws \Exception
     *
     * @return array|null [Address Components]
     */
    public function geocoder($varAddress, Map $objMap, ?int $intResults = 1): ?array // removed static because using service is not possible with
    {
        // Feature removed in 2.0
        throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['featureDeletedIn'], 'Geocoding by Google', '2.0'));
        $arrResults = null;

        // Before everything, check if we can geocode this
        if (Map::GEOCODING_PROVIDER_GMAP !== $objMap->geocodingProvider || !$objMap->geocodingProviderGmapKey) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']);
        }

        // Standardize the address to geocode
        $strAddress = '';
        $arrCountries = Util::getCountries();
        if (is_a($varAddress, MapItem::class)) {
            if ($varAddress->street) {
                $strAddress .= trim(preg_replace('/\s+/', ' ', strip_tags($varAddress->street)));
            }

            if ($varAddress->postal) {
                $strAddress .= ','.$varAddress->postal;
            }

            if ($varAddress->city) {
                $strAddress .= ','.$varAddress->city;
            }

            if ($varAddress->region) {
                $strAddress .= ','.$varAddress->region;
            }

            if ($varAddress->admin_lvl_1) {
                $args[] = 'state='.$varAddress->admin_lvl_1;
            }

            if ($varAddress->country) {
                $strAddress .= '&amp;region='.$arrCountries[$varAddress->country];
            }
        } else {
            $strAddress = $varAddress;
        }

        // Some String manips
        $strAddress = str_replace(' ', '+', $strAddress);

        // Then, cURL it baby.
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, \sprintf(static::$strGeocodingUrl, $strAddress, $this->encryption->encrypt_b64($objMap->geocodingProviderGmapKey)));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $geoloc = json_decode(curl_exec($ch), true);

        // Catch Error
        if ('OK' !== $geoloc['status']) {
            throw new \Exception($geoloc['error_message']);
        }

        // And return them
        if (1 === $intResults) {
            return ['lat' => $geoloc['results'][0]['geometry']['location']['lat'], 'lng' => $geoloc['results'][0]['geometry']['location']['lng']];
        }

        foreach ($geoloc['results'] as $result) {
            $arrResults[] = ['lat' => $result['geometry']['location']['lat'], 'lng' => $result['geometry']['location']['lng']];
        }

        return $arrResults;
    }
}
