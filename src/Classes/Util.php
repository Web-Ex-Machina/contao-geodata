<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\Classes;

use Contao\Database;
use Contao\Input;
use Contao\StringUtil;
use Contao\System;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide utilities function to Locations Extension.
 */
class Util
{
    /**
     * Calculates the great-circle distance between two points, with
     * the Vincenty formula.
     *
     * @param float $latitudeFrom  Latitude of start point in [deg decimal]
     * @param float $longitudeFrom Longitude of start point in [deg decimal]
     * @param float $latitudeTo    Latitude of target point in [deg decimal]
     * @param float $longitudeTo   Longitude of target point in [deg decimal]
     * @param float $earthRadius   Mean earth radius in [m]
     *
     * @return float Distance between points in [m] (same as earthRadius)
     */
    public static function vincentyGreatCircleDistance(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo,
        $earthRadius = 6371000
    ) {
        // convert from degrees to radians
        $latFrom = deg2rad($latitudeFrom);
        $lonFrom = deg2rad($longitudeFrom);
        $latTo = deg2rad($latitudeTo);
        $lonTo = deg2rad($longitudeTo);

        $lonDelta = $lonTo - $lonFrom;
        $a = (cos($latTo) * sin($lonDelta)) ** 2 +
        (cos($latFrom) * sin($latTo) - sin($latFrom) * cos($latTo) * cos($lonDelta)) ** 2;
        $b = sin($latFrom) * sin($latTo) + cos($latFrom) * cos($latTo) * cos($lonDelta);

        $angle = atan2(sqrt($a), $b);

        return $angle * $earthRadius;
    }

    /**
     * Find and replace Location tags.
     *
     * @return [type] [description]
     */
    public static function replaceInsertTags($tag, $blnCache, $strTagCache, $flags, $tags, $arrCache, $_rit, $_cnt)
    {
        $arrTag = explode('::', $tag);

        // Exist if the tested tag doesn't concern locations
        if ('wem_geodata' !== $arrTag[0]) {
            return false;
        }

        // Check if we asked for a precise location or the current one
        if (3 === \count($arrTag)) {
            $varLocation = $arrTag[1];
            $strField = $arrTag[2];
        } else {
            $varLocation = Input::get('auto_item');
            $strField = $arrTag[1];
        }

        // Before trying to find a specific location, make sure the field we want exists
        if (!Database::getInstance()->fieldExists($strField, MapItem::getTable())) {
            return false;
        }

        // Try to find the location, with the item given (return false if not found)
        if (!$objLocation = MapItem::findByIdOrAlias($varLocation)) {
            return false;
        }

        // Now we know everything is fine, return the field wanted
        return $objLocation->$strField;
    }

    /**
     * Copy of System::getCountries (because it is deprecated but handful).
     */
    public static function getCountries(): array
    {
        $arrCountries = System::getContainer()->get('contao.intl.countries')->getCountries();

        return array_combine(array_map('strtolower', array_keys($arrCountries)), $arrCountries);
    }

    /**
     * Try to find an ISO Code from the Country fullname.
     */
    public static function getCountryISOCodeFromFullname($strFullname)
    {
        $arrCountries = self::getCountries();

        foreach ($arrCountries as $strIsoCode => $strName) {
            // Use Generate Alias to handle little imperfections
            if (StringUtil::generateAlias($strName) === StringUtil::generateAlias($strFullname)) {
                return $strIsoCode;
            }
        }

        // If nothing, send an exception, because the name is wrong
        throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['countryNotFound'], $strFullname));
    }

    /**
     * Map a two-letter continent code onto the name of the continent.
     */
    public static function getContinents(): void
    {
        $CONTINENTS = [
            'AS' => 'Asia',
            'AN' => 'Antarctica',
            'AF' => 'Africa',
            'SA' => 'South America',
            'EU' => 'Europe',
            'OC' => 'Oceania',
            'NA' => 'North America',
        ];
    }

    /**
     * Return the Continent ISOCode of a Country.
     *
     * @param [String] $strCountry [Country ISOCode]
     *
     * @return [String] [Continent ISOCode]
     */
    public static function getCountryContinent($strCountry)
    {
        $COUNTRY_CONTINENTS = [
            'AF' => 'AS',
            'AX' => 'EU',
            'AL' => 'EU',
            'DZ' => 'AF',
            'AS' => 'OC',
            'AD' => 'EU',
            'AO' => 'AF',
            'AI' => 'NA',
            'AQ' => 'AN',
            'AG' => 'NA',
            'AR' => 'SA',
            'AM' => 'AS',
            'AW' => 'NA',
            'AU' => 'OC',
            'AT' => 'EU',
            'AZ' => 'AS',
            'BS' => 'NA',
            'BH' => 'AS',
            'BD' => 'AS',
            'BB' => 'NA',
            'BY' => 'EU',
            'BE' => 'EU',
            'BZ' => 'NA',
            'BJ' => 'AF',
            'BM' => 'NA',
            'BT' => 'AS',
            'BO' => 'SA',
            'BA' => 'EU',
            'BW' => 'AF',
            'BV' => 'AN',
            'BR' => 'SA',
            'IO' => 'AS',
            'BN' => 'AS',
            'BG' => 'EU',
            'BF' => 'AF',
            'BI' => 'AF',
            'KH' => 'AS',
            'CM' => 'AF',
            'CA' => 'NA',
            'CV' => 'AF',
            'KY' => 'NA',
            'CF' => 'AF',
            'TD' => 'AF',
            'CL' => 'SA',
            'CN' => 'AS',
            'CX' => 'AS',
            'CC' => 'AS',
            'CO' => 'SA',
            'KM' => 'AF',
            'CD' => 'AF',
            'CG' => 'AF',
            'CK' => 'OC',
            'CR' => 'NA',
            'CI' => 'AF',
            'HR' => 'EU',
            'CU' => 'NA',
            'CY' => 'AS',
            'CZ' => 'EU',
            'DK' => 'EU',
            'DJ' => 'AF',
            'DM' => 'NA',
            'DO' => 'NA',
            'EC' => 'SA',
            'EG' => 'AF',
            'SV' => 'NA',
            'GQ' => 'AF',
            'ER' => 'AF',
            'EE' => 'EU',
            'ET' => 'AF',
            'FO' => 'EU',
            'FK' => 'SA',
            'FJ' => 'OC',
            'FI' => 'EU',
            'FR' => 'EU',
            'GF' => 'SA',
            'PF' => 'OC',
            'TF' => 'AN',
            'GA' => 'AF',
            'GM' => 'AF',
            'GE' => 'AS',
            'DE' => 'EU',
            'GH' => 'AF',
            'GI' => 'EU',
            'GR' => 'EU',
            'GL' => 'NA',
            'GD' => 'NA',
            'GP' => 'NA',
            'GU' => 'OC',
            'GT' => 'NA',
            'GG' => 'EU',
            'GN' => 'AF',
            'GW' => 'AF',
            'GY' => 'SA',
            'HT' => 'NA',
            'HM' => 'AN',
            'VA' => 'EU',
            'HN' => 'NA',
            'HK' => 'AS',
            'HU' => 'EU',
            'IS' => 'EU',
            'IN' => 'AS',
            'ID' => 'AS',
            'IR' => 'AS',
            'IQ' => 'AS',
            'IE' => 'EU',
            'IM' => 'EU',
            'IL' => 'AS',
            'IT' => 'EU',
            'JM' => 'NA',
            'JP' => 'AS',
            'JE' => 'EU',
            'JO' => 'AS',
            'KZ' => 'AS',
            'KE' => 'AF',
            'KI' => 'OC',
            'KP' => 'AS',
            'KR' => 'AS',
            'KW' => 'AS',
            'KG' => 'AS',
            'LA' => 'AS',
            'LV' => 'EU',
            'LB' => 'AS',
            'LS' => 'AF',
            'LR' => 'AF',
            'LY' => 'AF',
            'LI' => 'EU',
            'LT' => 'EU',
            'LU' => 'EU',
            'MO' => 'AS',
            'MK' => 'EU',
            'MG' => 'AF',
            'MW' => 'AF',
            'MY' => 'AS',
            'MV' => 'AS',
            'ML' => 'AF',
            'MT' => 'EU',
            'MH' => 'OC',
            'MQ' => 'NA',
            'MR' => 'AF',
            'MU' => 'AF',
            'YT' => 'AF',
            'MX' => 'NA',
            'FM' => 'OC',
            'MD' => 'EU',
            'MC' => 'EU',
            'MN' => 'AS',
            'ME' => 'EU',
            'MS' => 'NA',
            'MA' => 'AF',
            'MZ' => 'AF',
            'MM' => 'AS',
            'NA' => 'AF',
            'NR' => 'OC',
            'NP' => 'AS',
            'AN' => 'NA',
            'NL' => 'EU',
            'NC' => 'OC',
            'NZ' => 'OC',
            'NI' => 'NA',
            'NE' => 'AF',
            'NG' => 'AF',
            'NU' => 'OC',
            'NF' => 'OC',
            'MP' => 'OC',
            'NO' => 'EU',
            'OM' => 'AS',
            'PK' => 'AS',
            'PW' => 'OC',
            'PS' => 'AS',
            'PA' => 'NA',
            'PG' => 'OC',
            'PY' => 'SA',
            'PE' => 'SA',
            'PH' => 'AS',
            'PN' => 'OC',
            'PL' => 'EU',
            'PT' => 'EU',
            'PR' => 'NA',
            'QA' => 'AS',
            'RE' => 'AF',
            'RO' => 'EU',
            'RU' => 'EU',
            'RW' => 'AF',
            'SH' => 'AF',
            'KN' => 'NA',
            'LC' => 'NA',
            'PM' => 'NA',
            'VC' => 'NA',
            'WS' => 'OC',
            'SM' => 'EU',
            'ST' => 'AF',
            'SA' => 'AS',
            'SN' => 'AF',
            'RS' => 'EU',
            'SC' => 'AF',
            'SL' => 'AF',
            'SG' => 'AS',
            'SK' => 'EU',
            'SI' => 'EU',
            'SB' => 'OC',
            'SO' => 'AF',
            'ZA' => 'AF',
            'GS' => 'AN',
            'ES' => 'EU',
            'LK' => 'AS',
            'SD' => 'AF',
            'SR' => 'SA',
            'SJ' => 'EU',
            'SZ' => 'AF',
            'SE' => 'EU',
            'CH' => 'EU',
            'SY' => 'AS',
            'TW' => 'AS',
            'TJ' => 'AS',
            'TZ' => 'AF',
            'TH' => 'AS',
            'TL' => 'AS',
            'TG' => 'AF',
            'TK' => 'OC',
            'TO' => 'OC',
            'TT' => 'NA',
            'TN' => 'AF',
            'TR' => 'AS',
            'TM' => 'AS',
            'TC' => 'NA',
            'TV' => 'OC',
            'UG' => 'AF',
            'UA' => 'EU',
            'AE' => 'AS',
            'GB' => 'EU',
            'UM' => 'OC',
            'US' => 'NA',
            'UY' => 'SA',
            'UZ' => 'AS',
            'VU' => 'OC',
            'VE' => 'SA',
            'VN' => 'AS',
            'VG' => 'NA',
            'VI' => 'NA',
            'WF' => 'OC',
            'EH' => 'AF',
            'YE' => 'AS',
            'ZM' => 'AF',
            'ZW' => 'AF',
        ];

        return $COUNTRY_CONTINENTS[strtoupper($strCountry)];
    }
}
