<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\Module;

use Contao\Config;
use Contao\ContentModel;
use Contao\Environment;
use Contao\FilesModel;
use Contao\Input;
use Contao\Model\Collection;
use Contao\Module;
use Contao\PageModel;
use Contao\Pagination;
use Contao\StringUtil;
use Exception;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemAttributeValue;
use WEM\GeoDataBundle\Model\MapItemCategory;

/**
 * Parent class for locations modules.
 */
abstract class Core extends Module
{
    public function getCategory($varItem)
    {
        if (\is_object($varItem)) {
            $arrItem = $varItem->row();
        } elseif (\is_array($varItem)) {
            $arrItem = $varItem;
        } elseif ($objItem = Category::findById($varItem)) {
            $arrItem = $objItem->row();
        } else {
            throw new Exception(\sprintf(
                $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noCategoryFound'],
                $varItem
            ));
        }

        // Get marker file
        if ($arrItem['marker'] && $objFile = FilesModel::findByUuid($arrItem['marker'])) {
            // Get size of the picture
            $sizes = getimagesize($objFile->path);
            $arrItem['marker'] = [];
            $arrItem['marker']['icon']['iconUrl'] = $objFile->path;
            $arrItem['marker']['icon']['iconSize'] = [$sizes[0], $sizes[1]];

            // Get the map config
            $objMap = Map::findById($arrItem['pid']);
            if (!$objMap) {
                throw new Exception('nothing to do here');
            }

            $mapConfig = unserialize($objMap->mapConfig);
            if (\is_array($mapConfig) && $mapConfig !== []) {
                foreach ($mapConfig as $v) {
                    // Skip configs not prefix by icon_
                    if (false === strpos($v['key'], 'icon_')) {
                        continue;
                    }

                    // Convert "values" who contains "," char into array values
                    if (strpos($v['value'], ',') > -1) {
                        $v['value'] = explode(',', $v['value']);
                    }

                    $v['key'] = explode('_', $v['key']);
                    $arrItem['marker']['icon'][$v['key'][1]] = $v['value'];
                }
            }

            // Get the marker config
            // https://leafletjs.com/reference-1.4.0.html#marker
            // https://leafletjs.com/reference-1.4.0.html#icon
            $data = unserialize($arrItem['markerConfig']);
            if (\is_array($data) && $data !== []) {
                foreach ($data as $v) {
                    // Convert "values" who contains "," char into array values
                    if (strpos($v['value'], ',') > -1) {
                        $v['value'] = explode(',', $v['value']);
                    }

                    if (strpos($v['key'], '_') > -1) {
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
    }

    public function getLocation($varItem, $blnAbsolute = false)
    {
        if (\is_object($varItem)) {
            $arrItem = $varItem->row();
        } elseif (\is_array($varItem)) {
            $arrItem = $varItem;
        } elseif ($objItem = MapItem::findByIdOrAlias($varItem)) {
            $arrItem = $objItem->row();
        } else {
            throw new Exception(\sprintf(
                $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationFound'],
                $varItem
            ));
        }

        // Format Address
        $arrItem['address'] = $arrItem['street'] . ' ' . $arrItem['postal'] . ' ' . $arrItem['city'];
        // Format website (we assume that every url is an external one)
        if (

            $arrItem['website'] && substr(
                $arrItem['website'],
                0,
                4
            ) !== 'http'

        ) {
            $arrItem['website'] = 'http://' . $arrItem['website'];
        }

        // Get category if ($arrItem['category']) {     $arrItem['category'] =

        // $this->getCategory($arrItem['category']); }
        $arrItem['category'] = [];
        $mapItemCategories = MapItemCategory::findItems(['pid' => $arrItem['id']]);
        if ($mapItemCategories instanceof Collection) {
            while ($mapItemCategories->next()) {
                $arrItem['category'][] = $this->getCategory($mapItemCategories->category);
            }
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
        $arrCountries = Util::getCountries();
        $strCountry = strtoupper($arrItem['country']);
        $strContinent = Util::getCountryContinent($strCountry);
        $arrItem['country'] = [
            'code' => $strCountry,
            'name' => $arrCountries[$arrItem['country']]
        ];
        $arrItem['continent'] = [
            'code' => $strContinent,
            'name' => $strContinent !== null ? $GLOBALS['TL_LANG']['CONTINENT'][$strContinent] : ''
        ];
        $strContent = '';
        $objElement = ContentModel::findPublishedByPidAndTable($arrItem['id'], 'tl_wem_map_item');
        if ($objElement !== null) {
            while ($objElement->next()) {
                $strContent .= $this->getContentElement($objElement->current());
            }
        }

        $arrItem['content'] = $strContent;
        // get attributes
        $arrItem['attributes'] = [];
        $attributes = MapItemAttributeValue::findItems(['pid' => $arrItem['id']]);
        if ($attributes instanceof \WEM\GeoDataBundle\Model\Collection) {
            while ($attributes->next()) {
                $arrItem['attributes'][$attributes->attribute] = [
                    'attribute' => $attributes->attribute,
                    'value' => $attributes->value,
                ];
            }
        }

        // Build the item URL
        $objMap = Map::findById($arrItem['pid']);
        $objPage = null;
        if ($objMap && $objMap->jumpTo) {
            $objPage = PageModel::findById($objMap->jumpTo);
        }

        if ($objPage instanceof PageModel) {
            $params = '/' . ($arrItem['alias'] ?: $arrItem['id']);
            $arrItem['url'] = StringUtil::ampersand(
                $blnAbsolute ? $objPage->getAbsoluteUrl($params) : $objPage->getFrontendUrl($params)
            );
        }

        // HOOK: add custom logic
        if (

            isset($GLOBALS['TL_HOOKS']['WEMGEODATAGETLOCATION']) && \is_array(
                $GLOBALS['TL_HOOKS']['WEMGEODATAGETLOCATION']
            )

        ) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATAGETLOCATION'] as $callback) {
                $arrItem = static::importStatic($callback[0])->{$callback[1]}($arrItem, $objMap, $objPage, $this);
            }
        }

        return $arrItem;
    }

    

    protected function getCategories()
    {
        $params = [];
        if ($this->wem_geodata_map) {
            $params['pid'] = $this->wem_geodata_map;
        } elseif ($this->wem_geodata_maps !== null) {
            $arrCategoriesIds = unserialize($this->wem_geodata_maps ?? '');
            if (! $arrCategoriesIds || empty($arrCategoriesIds)) {
                throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noCategoryConfigured']);
            }

            $params['pid'] = $arrCategoriesIds;
        }

        $objCategories = Category::findItems($params);
        if (! $objCategories instanceof Collection) {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['categoriesNotFound']);
        }

        $arrCategories = [];

        while ($objCategories->next()) {
            $arrCategories[] = $this->getCategory($objCategories->row());
        }

        return $arrCategories;
    }

    protected function countLocations($c = null): int
    {
        if ($c === null) {
            $c = ['published' => 1,
                'onlyWithCoords' => 1];
            if ($this->wem_geodata_map !== null && $this->wem_geodata_map !== 0) {
                $c['pid'] = $this->wem_geodata_map;
            } elseif (! empty($this->wem_geodata_maps)) {
                $pids = StringUtil::deserialize($this->wem_geodata_maps);
                if (! empty($pids)) {
                    $c['where'][] = \sprintf('pid IN (%s)', implode('', $pids));
                }
            }
        }

        return MapItem::countItems($c);
    }

    protected function getLocations($c = null): array
    {
        if ($c === null) {
            $c = ['published' => 1,
                'onlyWithCoords' => 1];
            if ($this->wem_geodata_map !== null) {
                $c['pid'] = $this->wem_geodata_map;
            } elseif (! empty($this->wem_geodata_maps)) {
                $pids = StringUtil::deserialize($this->wem_geodata_maps);
                if (! empty($pids)) {
                    $c['where'][] = \sprintf('pid IN (%s)', implode('', $pids));
                }
            }
        }

        $limit = 0;
        if (\array_key_exists('limit', $c)) {
            $limit = (int) $c['limit'];
            unset($c['limit']);
        }

        $offset = 0;
        if (\array_key_exists('offset', $c)) {
            $offset = (int) $c['offset'];
            unset($c['offset']);
        }

        $objLocations = MapItem::findItems($c, $limit, $offset);
        if (! $objLocations instanceof Collection) {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
        }

        $arrLocations = [];

        while ($objLocations->next()) {
            $arrLocations[] = $this->getLocation($objLocations->row());
        }

        return $arrLocations;
    }
}
