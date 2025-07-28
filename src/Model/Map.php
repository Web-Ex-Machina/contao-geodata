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

use Contao\StringUtil;
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

    /**
     * Format map config
     * 
     * @return array
     */
    public function getConfig(): array
    {
        $r = [];

        if (!$this->mapConfig) {
            return $r;
        }

        foreach (StringUtil::deserialize($this->mapConfig) as $arrRow) {
            if ($arrRow['value'] === 'true') {
                $varValue = true;
            } elseif ($arrRow['value'] === 'false') {
                $varValue = false;
            } elseif (\is_string($arrRow['value'])) {
                $varValue = html_entity_decode($arrRow['value']);
            } else {
                $varValue = $arrRow['value'];
            }

            if (str_contains($arrRow['key'], '_')) {
                $arrOption = explode('_', $arrRow['key']);
                $r[$arrOption[0]][$arrOption[1]] = $varValue;
            } else {
                $r['map'][$arrRow['key']] = $varValue;
            }
        }

        return $r;
    }
}
