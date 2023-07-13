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

namespace WEM\GeoDataBundle\Model;

use WEM\UtilsBundle\Model\Model as CoreModel;

/**
 * Reads and writes items.
 */
class MapItemCategory extends CoreModel
{
    /**
     * Table name.
     *
     * @var string
     */
    protected static $strTable = 'tl_wem_map_item_category';

    /**
     * Order colummn.
     *
     * @var string
     */
    protected static $strOrderColumn = 'created_at ASC';
}
