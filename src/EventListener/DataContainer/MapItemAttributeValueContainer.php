<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;

class MapItemAttributeValueContainer extends CoreContainer
{
    #[AsCallback(table: 'tl_wem_map_item_attribute_value', target: 'list.sorting.child_record')]
    public function listItems(array $arrRow): string
    {
        return $arrRow['attribute'] . ' ' . $arrRow['value'];
    }
}
