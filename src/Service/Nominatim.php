<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Service;

use Contao\Config;
use Exception;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide Nominatim utilities functions
 */
class Nominatim
{
    /**
     * Nominating Geocoding URL to request (sprintf pattern).
     *
     * @var string
     */
    protected $strGeocodingUrl = 'https://nominatim.openstreetmap.org/search%s&format=json&addressdetails=1&email=%s';

    /**
     * Return the coords lat/lng for a given address.
     *
     * @param string|MapItem $varAddress Address to geocode
     * @param Map            $objMap     Map Model
     * @param int            $intResults Number of API results wanted
     *
     * @return array [Address Components]
     */
    public function geocode(string|MapItem $varAddress, Map $objMap, int|null $intResults = 1): array 
    {
        // Before everything, check if we can geocode this
        if ('nominatim' !== $objMap->geocodingProvider) {
            throw new Exception(
                $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']
            );
        }

        if (empty($objMap->geocodingProviderNominatimReferer)) {
            throw new Exception(
                $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']
            );
        }

        // Standardize the address to geocode
        $args = [];
        if (is_a($varAddress, MapItem::class)) {
            if ($varAddress->street) {
                $args[] = 'street=' . trim(preg_replace('/\s+/', ' ', strip_tags($varAddress->street)));
            }

            if ($varAddress->postal) {
                $args[] = 'postalcode=' . $varAddress->postal;
            }

            if ($varAddress->city) {
                $args[] = 'city=' . $varAddress->city;
            }

            if ($varAddress->region) {
                $args[] = 'state=' . $varAddress->region;
            }

            if ($varAddress->admin_lvl_1) {
                $args[] = 'state=' . $varAddress->admin_lvl_1;
            }

            if ($varAddress->country) {
                $args[] = 'countrycodes=' . $varAddress->country;
            }

            $strAddress = '?' . implode('&', $args);
        } else {
            $strAddress = '?q=' . $varAddress;
        }

        // Some String manips
        $strAddress = str_replace(' ', '+', $strAddress);

        // Then, cURL it baby.
        $ch = curl_init();
        $strUrl = \sprintf($this->strGeocodingUrl, $strAddress, Config::get('adminEmail'));
        curl_setopt($ch, CURLOPT_URL, $strUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, Config::get('adminEmail'));
        curl_setopt($ch, CURLOPT_REFERER, $objMap->geocodingProviderNominatimReferer);
        $response = curl_exec($ch);
        $geoloc = json_decode($response, true);

        // Catch Error
        if (!$geoloc) {
            throw new Exception(\sprintf(
                $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['invalidRequest'],
                $strUrl
            ));
        }

        // And return them
        if (1 === $intResults) {
            return [
                'lat' => $geoloc[0]['lat'],
                'lng' => $geoloc[0]['lon'],
            ];
        }

        foreach ($geoloc as $result) {
            $arrResults[] = [
                'lat' => $result['lat'],
                'lng' => $result['lon'],
            ];
        }

        return $arrResults;
    }
}
