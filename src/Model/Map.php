<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Model;

use WEM\UtilsBundle\Model\Model as CoreModel;

/**
 * Reads and writes items.
 */
class Map extends CoreModel
{
    public const GEOCODING_PROVIDER_NOMINATIM = 'nominatim';

    public const MAP_PROVIDER_GMAP = 'gmaps';

    public const MAP_PROVIDER_LEAFLET = 'leaflet';

    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map';
}
