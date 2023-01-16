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

/**
 * Provide JVector utilities functions to Locations Extension.
 */
class JVector extends Controller
{
    /**
     * Default JVector Map Config.
     *
     * @return [Array]
     */
    public static function getDefaultConfig()
    {
        return [
            'provider' => 'jvector', 'zoomOnScroll' => 'false', 'panOnDrag' => 'false', 'regionsSelectable' => 'true', 'regionsSelectableOne' => 'true', 'markersSelectable' => 'true', 'markersSelectableOne' => 'true', 'mapBackground' => 'ffffff', 'regionBackground' => 'dddddd', 'regionBackgroundActive' => '999999', 'regionBackgroundHover' => '999999', 'regionBackgroundSelected' => '666666', 'regionBackgroundSelectedHover' => '666666', 'regionLock' => 'true', 'markerBackground' => '666666', 'markerBackgroundHover' => '999999', 'markerBackgroundSelected' => '999999',
        ];
    }
}
