<?php

declare(strict_types=1);

/**
 * Altrad Map Bundle for Contao Open Source CMS
 * Copyright (c) 2017-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-altrad-map-bundle
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-altrad-map-bundle/
 */

namespace WEM\GeoDataBundle\Module;

use WEM\GeoDataBundle\Controller\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Item;
use Contao\FilesModel;
use Contao\System;
use Contao\ContentModel;
use Contao\PageModel;
use Contao\Config;
use Contao\Module;

/**
 * Parent class for locations modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class Core extends Module
{
    protected function getCategories()
    {
        try {
            $objCategories = Category::findItems(['published' => 1, 'pid' => $this->wem_location_map]);

            if (!$objCategories) {
                throw new \Exception('No categories found for this map.');
            }

            $arrCategories = [];
            while ($objCategories->next()) {
                $arrCategories[] = $this->getCategory($objCategories->row());
            }

            return $arrCategories;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function getLocations($c = null)
    {
        try {
            if (null === $c) {
                $c = ['published' => 1, 'onlyWithCoords' => 1, 'pid' => $this->wem_location_map];
            }

            $objLocations = Item::findItems($c);

            if (!$objLocations) {
                throw new \Exception('No locations found for this map.');
            }

            $arrLocations = [];
            while ($objLocations->next()) {
                $arrLocations[] = $this->getLocation($objLocations->row());
            }

            return $arrLocations;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function getCategory($varItem)
    {
        try {
            if (\is_object($varItem)) {
                $arrItem = $varItem->row();
            } elseif (\is_array($varItem)) {
                $arrItem = $varItem;
            } elseif ($objItem = Category::findByPk($varItem)) {
                $arrItem = $objItem->row();
            } else {
                throw new \Exception('No category found for : '.$varItem);
            }

            // Get marker file
            if ($arrItem['marker'] && $objFile = FilesModel::findByUuid($arrItem['marker'])) {
                // Get size of the picture
                $sizes = getimagesize($objFile->path);
                $arrItem['marker'] = [];
                $arrItem['marker']['icon']['iconUrl'] = $objFile->path;
                $arrItem['marker']['icon']['iconSize'] = [$sizes[0], $sizes[1]];

                // Get the entire config
                // https://leafletjs.com/reference-1.4.0.html#marker
                // https://leafletjs.com/reference-1.4.0.html#icon
                $data = unserialize($arrItem['markerConfig']);
                if (\is_array($data) && !empty($data)) {
                    foreach ($data as $k => $v) {
                        // Convert "values" who contains "," char into array values
                        if (-1 < strpos($v['value'], ',')) {
                            $v['value'] = explode(',', $v['value']);
                        }

                        if (-1 < strpos($v['key'], '_')) {
                            $v['key'] = explode('_', $v['key']);
                            $arrItem['marker'][$v['key'][0]][$v['key'][1]] = $v['value'];
                        } else {
                            $arrItem['marker'][$v['key']] = $v['value'];
                        }
                    }
                }
            } else {
                unset($arrItem['marker']);
            }

            return $arrItem;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    protected function getLocation($varItem, $blnAbsolute = false)
    {
        try {
            if (\is_object($varItem)) {
                $arrItem = $varItem->row();
            } elseif (\is_array($varItem)) {
                $arrItem = $varItem;
            } elseif ($objItem = Item::findByIdOrAlias($varItem)) {
                $arrItem = $objItem->row();
            } else {
                throw new \Exception('No location found for : '.$varItem);
            }

            // Format Address
            $arrItem['address'] = $arrItem['street'].' '.$arrItem['postal'].' '.$arrItem['city'];

            // Format website (we assume that every url is an external one)
            if ($arrItem['website'] && 'http' !== substr($arrItem['website'], 0, 4)) {
                $arrItem['website'] = 'http://'.$arrItem['website'];
            }

            // Get category
            if ($arrItem['category']) {
                $arrItem['category'] = $this->getCategory($arrItem['category']);
            }

            // Get location picture
            if ($objFile = FilesModel::findByUuid($arrItem['picture'])) {
                $arrItem['picture'] = [
                    'path' => $objFile->path,
                    'extension' => $objFile->extension,
                    'name' => $objFile->name,
                ];
            } else {
                unset($arrItem['picture']);
            }

            // Get country and continent
            System::getCountries();
            $strCountry = strtoupper($arrItem['country']);
            $strContinent = Util::getCountryContinent($strCountry);
            $arrItem['country'] = ['code' => $strCountry, 'name' => $GLOBALS['TL_LANG']['CNT'][$arrItem['country']]];
            $arrItem['continent'] = ['code' => $strContinent, 'name' => $GLOBALS['TL_LANG']['CONTINENT'][$strContinent]];

            $strContent = '';
            $objElement = ContentModel::findPublishedByPidAndTable($arrItem['id'], 'tl_wem_item');
            if (null !== $objElement) {
                while ($objElement->next()) {
                    $strContent .= $this->getContentElement($objElement->current());
                }
            }
            $arrItem['content'] = $strContent;

            // Build the item URL
            if ($this->objJumpTo instanceof PageModel) {
                $params = (Config::get('useAutoItem') ? '/' : '/items/').($arrItem['alias'] ?: $arrItem['id']);
                $arrItem['url'] = ampersand($blnAbsolute ? $this->objJumpTo->getAbsoluteUrl($params) : $this->objJumpTo->getFrontendUrl($params));
            }

            return $arrItem;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
