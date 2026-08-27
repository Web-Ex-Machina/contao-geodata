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

use WEM\GeoDataBundle\Classes\Util;
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

    public function delete()
    {
        // remove links item <-> category
        $mapItem = MapItem::findById($this->pid);

        if ($mapItem) {
            $mapItem->refreshCategories([$this->category]);
        }

        return parent::delete();
    }
}
