<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\ContentModel;
use Contao\Controller;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\System;
use Exception;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\CountriesUtil;
use WEM\UtilsBundle\Classes\StringUtil;

/**
 * Common functions for job portfolios modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleController extends AbstractFrontendModuleController
{
    protected ContentUrlGenerator $cug;
    protected Map $map;
    protected ModuleModel $model;
    protected RequestStack $request;

    public function __construct() 
    {
        $this->cug = System::getContainer()->get('contao.routing.content_url_generator');
        $this->request = System::getContainer()->get('request_stack');
    }

    /**
     * Parse one or more items and return them as array.
     *
     * @throws \Exception
     */
    protected function parseItems(Collection $objItems): array
    {
        $limit = $objItems->count();

        if ($limit < 1) {
            return [];
        }

        $count = 0;
        $arrArticles = [];

        while ($objItems->next()) {
            $objItem = $objItems->current();

            $arrArticles[] = $this->parseItem(
                $objItem, 
                ((1 === ++$count) ? ' first' : '') . (($count === $limit) ? ' last' : '') . ((0 === ($count % 2)) ? ' odd' : ' even'), 
                $count
            );
        }

        return $arrArticles;
    }

    /**
     * Parse an item and return it as string.
     *
     * @throws \Exception
     */
    protected function parseItem(MapItem $objItem, string $strClass = '', int $intCount = 0): string
    {
        $objTemplate = new FrontendTemplate($this->model->wem_geodata_item_template);
        $objTemplate->setData($objItem->row());

        if ('' !== $objItem->cssClass) {
            $strClass = ' '.$objItem->cssClass.$strClass;
        }

        $objTemplate->class = $strClass;
        $objTemplate->count = $intCount;

        // Parse categories
        $objCategories = $objItem->getRelated('categories');
        $objTemplate->categories = null !== $objCategories ? $objCategories->fetchAll() : [];

        // Format country & continent
        $arrCountries = CountriesUtil::getCountries();
        $strCountry = strtoupper($objItem->country);

        $strContinent = CountriesUtil::getCountryContinent($strCountry);
        $objTemplate->country = [
            'code' => $strCountry,
            'name' => $arrCountries[$objItem->country]
        ];
        $objTemplate->continent = [
            'code' => $strContinent,
            'name' => $strContinent !== null ? $GLOBALS['TL_LANG']['CONTINENT'][$strContinent] : ''
        ];

        // Format Address
        $objTemplate->address = $objItem->street . ' ' . $objItem->postal . ' ' . $objItem->city;
        
        // Format website (we assume that every url is an external one)
        if ($objItem->website && 'http' !== substr($objItem->website, 0, 4)) {
            $objTemplate->website = 'https://' . $objItem->website;
        }

        // Retrieve item teaser
        if ($objItem->teaser) {
            $objTemplate->hasTeaser = true;
            $objTemplate->teaser = strip_tags($objItem->teaser);
        }

        // Parse the URL if we have a jumpTo configured
        if ($objItem->getRelated('pid')->jumpTo) {
            $objTemplate->jumpTo = $this->getUrl($objItem);
        }

        // Add an image
        if ($objItem->picture) {
            $objFile = FilesModel::findByUuid($objItem->picture);

            $imgSize = null;
            if ($this->model->imgSize) {
                $size = StringUtil::deserialize($this->model->imgSize);

                if ($size[0] > 0 || $size[1] > 0 || is_numeric($size[2]) || ($size[2][0] ?? null) === '_') {
                    $imgSize = $this->model->imgSize;
                }
            }

            $figure = System::getContainer()
                ->get('contao.image.studio')
                ->createFigureBuilder()
                ->from($objItem->picture)
                ->setSize($imgSize)
                ->buildIfResourceExists()
            ;

            if (null !== $figure) {
                $figure->applyLegacyTemplateData($objTemplate);
            }

            // Send also the data for flexible behavior
            $objTemplate->pictureSrc = $objFile;
        }

        // Retrieve item content
        $objTemplate->text = $this->getContent($objItem);
        $objTemplate->hasText = static fn (): bool => ContentModel::countPublishedByPidAndTable($objItem->id, MapItem::getTable()) > 0;

        return $objTemplate->parse();
    }

    protected function loadMap(): void
    {
        if (null === $this->model) {
            return;
        }

        if (0 === $this->model->wem_geodata_map) {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['GEODATA']['ERR']['mapCannotBeInitialized']);
        }

        $this->map = Map::findById($this->model->wem_geodata_map);
    }

    protected function getFiltersModule(): string
    {
        if ($this->model->wem_geodata_addFilters) {
            return Controller::getFrontendModule($this->model->wem_geodata_filters_module);
        }

        return '';
    }

    protected function countItems(): int
    {
        return MapItem::countItems($this->config);
    }

    protected function findItems(): Collection
    {
        return MapItem::findItems($this->config, (int) $this->limit ?: 0, (int) $this->offset ?: 0, $this->options);
    }

    /**
     * Generate item URL
     * 
     * @param MapItem - Map item we want the url
     * @param array - Params to add
     * @param int - URL format (check UrlGeneratorInterface)
     * 
     * @return string
     */
    protected function getUrl(MapItem $item, array $params = [], int $format = UrlGeneratorInterface::ABSOLUTE_PATH): string
    {
        return $this->cug->generate(
            $item,
            $params, 
            $format,
        );
    }

    /**
     * Get item content
     * 
     * @param MapItem - Map item we want the content
     * 
     * @return string
     */
    public function getContent(MapItem $item): string
    {
        $strText = '';
        $objElement = ContentModel::findPublishedByPidAndTable(
            $item->id, 
            'tl_wem_map_item',
        );

        if (null !== $objElement) {
            while ($objElement->next()) {
                $strText .= Controller::getContentElement($objElement->current());
            }
        }

        return $strText;
    }
}
