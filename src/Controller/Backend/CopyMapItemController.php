<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Controller\Backend;

use Contao\CoreBundle\Controller\AbstractController;
use Contao\Controller;
use Contao\DataContainer;
use Contao\Environment;
use Exception;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide backend functions to Locations Extension.
 */
class CopyMapItemController extends AbstractController
{
    public function __construct(

    ) {
    }

    public function run(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $objMapOld = Map::findById($dc->id);
        if (!$objMapOld) {
            throw new Exception(\sprintf('Unable to find map %s', $dc->id));
        }

        $arrData = $objMapOld->row();
        unset($arrData['id']);

        $objMap = new Map();
        $objMap->setRow($arrData);
        $objMap->save();

        $objMapItems = $objMapOld->getMapItems();

        if ($objMapItems && 0 < $objMapItems->count()) {
            while ($objMapItems->next()) {
                $arrData = $objMapItems->row();
                $arrData['pid'] = $objMap->id;
                unset($arrData['id']);

                $objMapItem = new MapItem();
                $objMapItem->setRow($arrData);
                $objMapItem->save();
            }
        }

        $url = Environment::get('uri');
        $url = str_replace(['&key=copy_map_item', '&id=' . $dc->id], ['&act=edit', '&id=' . $objMap->id], $url);

        Controller::redirect($url);
    }
}