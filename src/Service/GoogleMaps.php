<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Service;

use Contao\Config;
use Exception;
use Override;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\Encryption;

/**
 * Provide Google Maps utilities functions
 */
class GoogleMaps extends Provider
{
    
    public function __construct(
        protected readonly Encryption $encryption,
    ) {   
        parent::__construct();
    }

	public function loadMapAssets(): void
	{
        $v = Config::get('wem_geodata_assets_version') ?: WEM_GEODATA_COMBINER_VERSION;

        if (!$this->map->mapProviderGmapKey) {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['GEODATA']['ERR']['gmapNeedsAPIKey']);
        }

        $remoteJs = \sprintf(
            'https://maps.googleapis.com/maps/api/js?key=%s',
            $this->encryption->decrypt_b64($this->map->mapProviderGmapKey)
        );

        $GLOBALS['TL_CSS'][] = 'bundles/wemgeodata/css/gmaps.css|' . $v;
        $GLOBALS['TL_JAVASCRIPT'][] = $remoteJs . '|' . $v;
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/js/default.js|' . $v;
        $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/wemgeodata/js/gmaps.js|' . $v;
	}
}