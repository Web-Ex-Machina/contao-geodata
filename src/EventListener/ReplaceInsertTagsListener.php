<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\EventListener;

use Contao\Database;
use WEM\GeoDataBundle\Model\MapItem;

class ReplaceInsertTagsListener
{
    /**
     * @param string $tag The tag
     *
     * @return false|string the value of the requested field for the given location or false if the tag is not related to geodata or if the location or field is not found
     */
    public function __invoke(string $tag)
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
}
