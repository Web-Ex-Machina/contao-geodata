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

namespace WEM\GeoDataBundle\DataContainer;

use Contao\Backend;

class Map extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Generate the default map config array.
     *
     * @param [Array] $varValue
     *
     * @return [Array]
     */
    public function getDefaultMapConfig($varValue, $objDc)
    {
        if (!$varValue) {
            switch ($objDc->activeRecord->mapProvider) {
                // case 'jvector':
                //     $arrConfig = \WEM\GeoDataBundle\Controller\Provider\JVector::getDefaultConfig();
                //     break;

                case 'leaflet':
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
     *
     * @param [Array] $varValue
     *
     * @return [Array]
     */
    public function generateExcelPattern($varValue)
    {
        if (!$varValue) {
            $varValue = [
                ['key' => 'title', 'value' => 'A'], ['key' => 'lat', 'value' => 'B'], ['key' => 'lng', 'value' => 'C'], ['key' => 'street', 'value' => 'D'], ['key' => 'postal', 'value' => 'E'], ['key' => 'city', 'value' => 'F'], ['key' => 'region', 'value' => 'G'], ['key' => 'country', 'value' => 'H'], ['key' => 'phone', 'value' => 'I'], ['key' => 'email', 'value' => 'J'], ['key' => 'website', 'value' => 'K'],
            ];
        }

        return $varValue;
    }
}
