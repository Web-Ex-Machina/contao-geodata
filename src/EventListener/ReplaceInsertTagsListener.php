<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
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
    public function __invoke(
        string $tag
    ) {
        $arrTag = explode('::', $tag);

        // Exist if the tested tag doesn't concern locations
        if ($arrTag[0] !== 'wem_geodata') {
            return false;
        }

        // Check if we asked for a precise location or the current one
        if (\count($arrTag) === 3) {
            $varLocation = $arrTag[1];
            $strField = $arrTag[2];
        } else {
            $varLocation = Input::get('auto_item');
            $strField = $arrTag[1];
        }

        // Before trying to find a specific location, make sure the field we want exists
        if (

            ! Database::getInstance()->fieldExists(
                $strField,
                MapItem::getTable()
            )

        ) {
            return false;
        }

        // Try to find the location, with the item given (return false if not found)
        if (

            ! $objLocation = MapItem::findByIdOrAlias(
                $varLocation
            )

        ) {
            return false;
        }

        // Now we know everything is fine, return the field wanted
        return $objLocation->{$strField};
    }
}
