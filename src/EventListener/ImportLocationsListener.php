<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2023-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\EventListener;

use Contao\Database;
use Contao\File;
use Contao\Message;
use Contao\StringUtil;
use PhpOffice\PhpSpreadsheet\IOFactory;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemAttributeValue;

/**
 * Provide utilities function to Locations Extension.
 */
class ImportLocationsListener
{
    public function importPostalCodes($arrUploaded, $arrExcelPattern, $objMap, $objModule): void
    {
        foreach ($arrUploaded as $strFile) {
            $objFile = new File($strFile, true);
            $spreadsheet = IOFactory::load(TL_ROOT.'/'.$objFile->path);
            $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
            $arrLocations = [];

            $intCreated = 0;
            $intUpdated = 0;
            $intDeleted = 0;
            $arrNewLocations = [];
            $arrNewLocationAttributes = [];
            $arrDepCache = [];

            foreach ($sheetData as $r => $arrRow) {
                // Skip first row
                if ('XCP_0' === $arrRow['A']) {
                    continue;
                }

                // Find parent
                $objLocation = MapItem::findItems(['pid' => $objMap->id, 'title' => $arrRow['F']], 1);

                // Create if don't exists
                if (!$objLocation) {
                    $objLocation = new MapItem();
                    $objLocation->createdAt = time();
                    $objLocation->pid = $objMap->id;
                    $objLocation->published = 1;
                    $objLocation->save();
                    ++$intCreated;
                } else {
                    ++$intUpdated;
                }

                $objLocation->tstamp = time();
                $objLocation->title = $arrRow['F'];
                $objLocation->alias = StringUtil::generateAlias($arrRow['F']);
                $objLocation->country = 'fr';

                // Extract admin_lvl_2 (2 first chars) and add it to perso sheet
                $strDep = substr($arrRow['A'], 0, 2);

                if ($strDep && '' !== $strDep) {
                    if (\in_array($objLocation->id, $arrDepCache, true)) {
                        $arrLocationDeps = $arrDepCache[$objLocation->id];
                    } else {
                        if (' / ' === substr($objLocation->admin_lvl_2, 0, 3)) {
                            $objLocation->admin_lvl_2 = substr($objLocation->admin_lvl_2, 3);
                        }

                        $arrLocationDeps = explode(' / ', $objLocation->admin_lvl_2);
                        sort($arrLocationDeps);
                    }

                    if (!\in_array($strDep, $arrLocationDeps, true)) {
                        $arrLocationDeps[] = $strDep;
                        sort($arrLocationDeps);
                        $arrDepCache[$objLocation->id] = $arrLocationDeps;
                    }

                    $objLocation->admin_lvl_2 = implode(' / ', $arrLocationDeps);
                }

                $objLocation->save();
                $arrNewLocations[] = $objLocation->id;

                // Find attribute (postal code here)
                $objLocationAttributeValue = MapItemAttributeValue::findItems(['pid' => $objLocation->id, 'attribute' => 'postal', 'value' => $arrRow['A']], 1);

                // Create if don't exists
                if (!$objLocationAttributeValue) {
                    $objLocationAttributeValue = new MapItemAttributeValue();
                    $objLocationAttributeValue->createdAt = time();
                    $objLocationAttributeValue->pid = $objLocation->id;
                    $objLocationAttributeValue->attribute = 'postal';
                }

                $objLocationAttributeValue->tstamp = time();
                $objLocationAttributeValue->value = $arrRow['A'];
                $objLocationAttributeValue->save();
                $arrNewLocationAttributes[] = $objLocationAttributeValue->id;
            }

            $objLocations = MapItem::findItems(['pid' => $objMap->id]);
            while ($objLocations->next()) {
                if (!\in_array($objLocations->id, $arrNewLocations, true)) {
                    $objLocations->delete();
                    ++$intDeleted;
                }
            }

            $strSql = sprintf('DELETE FROM tl_wem_map_item_attribute_value WHERE pid IN (%s) AND attribute = "postal" AND id NOT IN(%s)', implode(',', $arrNewLocations), implode(',', $arrNewLocationAttributes));
            Database::getInstance()->prepare($strSql)->execute();

            Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['createdConfirmation'], $intCreated));
            Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['updatedConfirmation'], $intUpdated));
            Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['deletedConfirmation'], $intDeleted));
        }
    }
}
