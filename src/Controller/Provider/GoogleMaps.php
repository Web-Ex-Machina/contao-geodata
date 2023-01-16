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

namespace WEM\GeoDataBundle\Controller\Provider;

use Contao\Controller;
use Contao\Encryption;
use Contao\System;

/**
 * Provide Google Maps utilities functions to Locations Extension.
 */
class GoogleMaps extends Controller
{
    /**
     * Google Map Geocoding URL to request (sprintf pattern).
     *
     * @var string
     */
    protected static $strGeocodingUrl = 'https://maps.googleapis.com/maps/api/geocode/json?address=%s&key=%s';

    /**
     * Return the coords lat/lng for a given address.
     *
     * @param [Mixed]   $varAddress [Address to geocode, can be a String, or a Location Model]
     * @param [Object]  $objMap     [Map Model]
     * @param [Integer] $intResults [Number of API results wanted]
     *
     * @return [Array] [Address Components]
     */
    public static function geocoder($varAddress, $objMap, $intResults = 1)
    {
        // Before everything, check if we can geocode this
        if ('gmaps' !== $objMap->geocodingProvider || !$objMap->geocodingProviderGmapKey) {
            throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']);
        }
        // Standardize the address to geocode
        $strAddress = '';
        $arrCountries = System::getCountries();
        if (\is_object($varAddress)) {
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
        curl_setopt($ch, CURLOPT_URL, sprintf(static::$strGeocodingUrl, $strAddress, Encryption::decrypt($objMap->geocodingProviderGmapKey)));
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
