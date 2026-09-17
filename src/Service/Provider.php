<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Service;

use Contao\Config;
use Exception;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide generic utilities functions for services
 */
abstract class Provider
{
    protected Map $map;

	public function __construct()
	{
	}

    public function loadMap(Map $map): void
    {
        $this->map = $map;
    }
}