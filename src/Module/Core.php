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

namespace WEM\GeoDataBundle\Module;

use Contao\Config;
use Contao\ContentModel;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Input;
use Contao\Module;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Contao\System;
use WEM\GeoDataBundle\Controller\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Item;
use WEM\GeoDataBundle\Model\Map;

/**
 * Parent class for locations modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class Core extends Module
{
    /**
     * Build Pagination.
     *
     * @param int $intTotal Number of items
     *
     * @return [Void]
     */
    protected function buildPagination(int $intTotal): void
    {
        $total = $intTotal - $this->offset;

        // Split the results
        if ($this->perPage > 0 && (!isset($this->limit) || $this->numberOfItems > $this->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->id;
            $page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($page < 1 || $page > max(ceil($total / $this->perPage), 1)) {
                throw new \Exception('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->perPage;
            $this->offset += (max($page, 1) - 1) * $this->perPage;
            $skip = (int) $this->skipFirst;

            // Overall limit
            if ($this->offset + $this->limit > $total + $skip) {
                $this->limit = $total + $skip - $this->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->perPage, Config::get('maxPaginationLinks') ?? 7, $id);
            $this->Template->pagination = $objPagination->generate("\n  ");
        }
    }

    protected function getCategories()
    {
        try {
            $objCategories = Category::findItems(['published' => 1, 'pid' => $this->wem_geodata_map]);

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
                $c = ['published' => 1, 'onlyWithCoords' => 1];
                if (null !== $this->wem_geodata_map) {
                    $c['pid'] = $this->wem_geodata_map;
                } elseif (!empty($this->wem_geodata_maps)) {
                    $pids = StringUtil::deserialize($this->wem_geodata_maps);
                    if (!empty($pids)) {
                        $c['where'][] = sprintf('pid IN (%s)', implode('', $pids));
                    }
                }
            }

            $limit = 0;
            if (\array_key_exists('limit', $c)) {
                $limit = $c['limit'];
                unset($c['limit']);
            }
            $offset = 0;
            if (\array_key_exists('offset', $c)) {
                $offset = $c['offset'];
                unset($c['offset']);
            }

            $objLocations = Item::findItems($c, $limit, $offset);

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
            $objMap = Map::findByPk($arrItem['pid']);
            if ($objMap && $objMap->jumpTo) {
                $objPage = PageModel::findByPk($objMap->jumpTo);
            }
            if ($objPage instanceof PageModel) {
                // if ($this->objJumpTo instanceof PageModel) {
                $params = (Config::get('useAutoItem') ? '/' : '/items/').($arrItem['alias'] ?: $arrItem['id']);
                $arrItem['url'] = ampersand($blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params));
            }

            return $arrItem;
        } catch (\Exception $e) {
            throw $e;
        }
    }
}
