<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\DataContainer;

class MapItemAttributeValue extends CoreContainer
{
    /**
     * Design each row of the DCA.
     */
    public function listItems(array $arrRow): string
    {
        return $arrRow['attribute'] . ' ' . $arrRow['value'];
    }
}
