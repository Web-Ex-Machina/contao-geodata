<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Config;
use Contao\ContentModel;
use Contao\Controller;
use Contao\FilesModel;
use Contao\FrontendTemplate;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\System;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\MapItemAttributeValue;
use WEM\GeoDataBundle\Model\MapItemCategory;
use WEM\GeoDataBundle\Service\Leaflet;
use WEM\UtilsBundle\Classes\CountriesUtil;
use WEM\UtilsBundle\Classes\DcaUtil;
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
    protected Request $request;
    protected Leaflet $service;

    protected ?array $baseConfig = [];
    protected array $filters = [];

    public function __construct() 
    {
        $this->cug = System::getContainer()->get('contao.routing.content_url_generator');
    }

    protected function getCategories(): array
    {
        $params = [];
        if ($this->model->wem_geodata_map) {
            $params['pid'] = $this->model->wem_geodata_map;
        } else {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['GEODATA']['ERROR']['noCategoryConfigured']);
        }

        $objCategories = Category::findItems($params);

        if (!$objCategories instanceof Collection) {
            throw new Exception($GLOBALS['TL_LANG']['WEM']['GEODATA']['ERROR']['categoriesNotFound']);
        }

        $arrCategories = [];

        while ($objCategories->next()) {
            $arrCategories[] = $this->getCategory($objCategories->current());
        }

        return $arrCategories;
    }

    public function getCategory(Category $item): array
    {
        $arrItem = $item->row();

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

            $mapConfig = StringUtil::deserialize($objMap->mapConfig);
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
            $data = StringUtil::deserialize($arrItem['markerConfig']);
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

            if (
                !($arrItem['marker']['icon']['iconUrl'] ?? false)
                || !($arrItem['marker']['icon']['iconSize'] ?? false)
                || !($arrItem['marker']['icon']['iconAnchor'] ?? false)
                || !($arrItem['marker']['icon']['popupAnchor'] ?? false)
            ) {
                try{
                    // retrieve default map category
                    // $objDefaultCategory = Category::findItems(['pid'=>$arrItem['pid'],'is_default'=>1]);
                    // if(!$objDefaultCategory){
                    //     throw new Exception('nothing to do here');
                    // }
                    // $objDefaultCategory = $objDefaultCategory->current();
                    // if((int) $objDefaultCategory->id === (int) $arrItem['id']){
                    //     throw new Exception('nothing to do here');
                    // }
                    // retrieve map
                    $objMap = Map::findByPk($arrItem['pid']);

                    if (!$objMap){
                        throw new Exception('nothing to do here');
                    }

                    $mapConfig = StringUtil::deserialize($objMap->mapConfig) ?? [];
                    // set missing infos
                    if(!($arrItem['marker']['icon']['iconUrl'] ?? false)
                    && $mapConfig['icon_iconUrl'] ?? false
                    ){
                        $arrItem['marker']['icon']['iconUrl'] = $mapConfig['icon_iconUrl'];
                    }
                    if(!($arrItem['marker']['icon']['iconSize'] ?? false)
                    && $mapConfig['icon_iconSize'] ?? false
                    ){
                        $arrItem['marker']['icon']['iconSize'] = explode(',',$mapConfig['icon_iconSize']);
                    }
                    if(!($arrItem['marker']['icon']['iconAnchor'] ?? false)
                    && $mapConfig['icon_iconAnchor'] ?? false
                    ){
                        $arrItem['marker']['icon']['iconAnchor'] = explode(',',$mapConfig['iconAnchor']);
                    }
                    if(!($arrItem['marker']['icon']['popupAnchor'] ?? false)
                    && $mapConfig['icon_popupAnchor'] ?? false
                    ){
                        $arrItem['marker']['icon']['popupAnchor'] = explode(',',$mapConfig['popupAnchor']);
                    }
                } catch(Exception $e) {
                    // do nothing
                }
            }

        return $arrItem;
    }

    public function formatItems(Collection $objItems): array
    {
        $arrItems = [];

        while ($objItems->next()) {
            $arrItems[] = $this->formatItem($objItems->current());
        }

        return $arrItems;
    }

    public function formatItem(MapItem $objItem, bool $blnAbsolute = false): array
    {
        $arrItem = $objItem->row();

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
            $arrItem['website'] = 'https://' . $arrItem['website'];
        }

        $arrItem['category'] = [];
        $mapItemCategories = MapItemCategory::findItems(['pid' => $arrItem['id']]);
        if ($mapItemCategories instanceof Collection) {
            while ($mapItemCategories->next()) {
                $arrItem['category'][] = $this->getCategory($mapItemCategories->getRelated('category'));
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
        $arrCountries = CountriesUtil::getCountries();
        $strCountry = strtoupper($arrItem['country']);
        $strContinent = CountriesUtil::getCountryContinent($strCountry);
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

        $objPage = $this->getUrl(
            $objItem, 
            [], 
            $blnAbsolute ? UrlGeneratorInterface::ABSOLUTE_PATH : UrlGeneratorInterface::RELATIVE_PATH
        );

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

        // Retrieve item map
        $objTemplate->map = $this->getMapModule();

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

        switch ($this->map->mapProvider) {
            case Map::MAP_PROVIDER_LEAFLET:
                $this->service = System::getContainer()->get('wem.geodata.service.leaflet');
            break;
            case Map::MAP_PROVIDER_GMAP:
                $this->service = System::getContainer()->get('wem.geodata.service.google_maps');
            break;
            default:
                throw new Exception($GLOBALS['TL_LANG']['WEM']['GEODATA']['ERR']['serviceCannotBeInitialized']);
        }

        $this->config = [
            'pid' => $this->map->id,
            'published' => 1,
        ];
        $this->baseConfig = $this->config;
    }

    protected function getFiltersModule(): string
    {
        if ($this->model->wem_geodata_addFilters) {
            // Retrieve filters
            $this->buildFilters();

            // Add search filter
            $this->addSearchFilter();

            $objTemplate = new FrontendTemplate('mod_wem_geodata_filters');

            $objTemplate->filters = $this->filters;
            $objTemplate->moduleId = $this->model->id;

            // Define where the form is redirected
            if ($this->model->jumpTo) {
                $page = PageModel::findById($this->model->jumpTo);
                $objTemplate->formAction = $this->contentUrlGenerator->generate($page);
            } else {
                $objTemplate->formAction = $this->request->getRequestUri();
            }

            // Reset link
            if ($this->config !== $this->baseConfig) {
                $objTemplate->formReset = $this->request->getPathInfo();
            }

            if (
                Input::post('TL_AJAX') 
                && (int) $this->model->id === (int) Input::post('module')
                && 'getFilters' === Input::post('action')
            ) {
                return new Response($objTemplate->parse(), Response::HTTP_OK);
            }

            return $objTemplate->parse();
        }

        return '';
    }

    protected function getListModule(): string
    {
        if ($this->model->wem_geodata_addList) {
            return Controller::getFrontendModule($this->model->wem_geodata_list_module);
        }

        return '';
    }

    protected function getMapModule(): string
    {
        if ($this->model->wem_geodata_addMap) {
            return Controller::getFrontendModule($this->model->wem_geodata_map_module);
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

    /**
     * Catch Ajax Requests.
     */
    protected function handleAjaxRequests(): Response
    {
        try {
            switch (Input::post('action')) {
                case 'getLocationsList':
                    return new Response($this->getListModule(), Response::HTTP_OK);
                    break;
                case 'getLocationsItems':
                    $this->buildFilters();
                    $this->limit = Input::post('limit') ? (int) Input::post('limit') : 50;
                    $this->offset = Input::post('offset') ? (int) Input::post('offset') : 0;
                    $objItems = $this->findItems();

                    if (!$objItems) {
                        return new Response("No items", Response::HTTP_NO_CONTENT);
                    }

                    $arrItems = [];
                    while ($objItems->next()) {
                        $arrItems[$objItems->id] = $this->parseItem($objItems->current());
                    }

                    return new JsonResponse($arrItems, Response::HTTP_OK);
                    break;
                default:
                    throw new Exception(\sprintf(
                        $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['unknownAjaxRequest'],
                        Input::post('action')
                    ));
            }
        } catch (Exception $exception) {
            $arrResponse = [
                'status' => 'error',
                'msg' => $exception->getMessage(),
                'trace' => $exception->getTrace(),
            ];
        }

        // Add Request Token to JSON answer and return
        $arrResponse['rt'] = System::getContainer()->get(
            'contao.csrf.token_manager'
        )->getDefaultTokenValue();

        return new JsonResponse($arrResponse, Response::HTTP_OK);
    }

    /**
     * Retrieve filters.
     */
    protected function getFilters(): array
    {
        return StringUtil::deserialize($this->model->wem_geodata_filters_fields);
    }

    /**
     * Retrieve filter options
     */
    protected function getFilterOptions(string $f): Collection
    {
        return MapItem::findItems($this->baseConfig, 0, 0, ['group' => $f]);
    }

    /**
     * Retrieve list filters.
     */
    protected function buildFilters(): void
    {
        // Retrieve and format dropdowns filters
        $filters = $this->getFilters();

        if (\is_array($filters) && [] !== $filters) {
            foreach ($filters as $f) {
                if ($this->shouldBeSkipped($f . ' != ""')) {
                    continue;
                }

                $this->addFilter($f);
            }
        }
    }

    protected function addFilter(string $f): void
    {
        $field = DcaUtil::getFieldConfig($f, MapItem::getTable());

        $fName = \sprintf(
            'geodata_filter_%s%s', 
            $f, 
            DcaUtil::isFieldMultiple($field) ? '[]' : ''
        );
        $fGet = \sprintf('geodata_filter_%s', $f);

        $filter = [
            'type' => $field['inputType'],
            'name' => $fName,
            'label' => $field['label'][0] ?? $f,
            'value' => Input::get($fGet) ?: '',
            'options' => [],
            'multiple' => DcaUtil::isFieldMultiple($field),
        ];

        switch ($field['inputType']) {
            case 'select':
                if (\array_key_exists('options_callback', $field) && \is_array($field['options_callback'])) {
                    $strClass = $field['options_callback'][0];
                    $strMethod = $field['options_callback'][1];

                    $callback = new $strClass();
                    $options = $callback->{$strMethod}($this->map);
                } elseif (\array_key_exists('options_callback', $field) && \is_callable($field['options_callback'])) {
                    $options = $field['options_callback']($this);
                } else {
                    $opts = $field['options'];

                    if (is_array($opts) && !empty($opts)) {

                        // There are two types of array 
                        // classic key - value
                        // contao arr[value] - arr[label]
                        if (\is_array(array_first($opts)) && \array_key_exists('value', array_first($opts))) {
                            foreach($opts as $opt) {
                                $options[$opt['value']] = $opt['label'];
                            }
                        } else {
                            $options = $opts;
                        }        
                    }
                }

                foreach ($options as $value => $label) {
                    if (\is_array($label)) {
                        foreach ($label as $subValue => $subLabel) {

                            $statement = DcaUtil::isFieldMultiple($field) 
                                ? $f . ' LIKE "%%'. $subValue .'%%"' 
                                : $f . ' = "'. $subValue .'"'
                            ;

                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$value]['options'][] = [
                                'value' => $subValue,
                                'label' => $subLabel,
                                'selected' => $this->isOptionSelected($fGet, $subValue, DcaUtil::isFieldMultiple($field)),
                            ];
                        }
                    } else {
                        $statement = DcaUtil::isFieldMultiple($field) 
                            ? $f . ' LIKE "%%'. $value .'%%"' 
                            : $f . ' = "'. $value .'"'
                        ;
                        
                        if ($this->shouldBeSkipped($statement)) {
                            return;
                        }

                        $filter['options'][] = [
                            'value' => $value,
                            'label' => $label,
                            'selected' => $this->isOptionSelected($fGet, $value, DcaUtil::isFieldMultiple($field)),
                        ];
                    }
                }

                break;

            case 'listWizard':
                $objOptions = $this->getFilterOptions($f);

                if ($objOptions) {
                    $filter['type'] = 'select';
                    if (DcaUtil::isFieldMultiple($field)) {
                        $filter['name'] .= '[]';
                    }

                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            return;
                        }

                        $subOptions = StringUtil::deserialize($objOptions->{$f});
                        foreach ($subOptions as $subOption) {
                            $statement = DcaUtil::isFieldMultiple($field) 
                                ? $f . ' LIKE "%%'. $subOption .'%%"' 
                                : $f . ' = "'. $subOption .'"'
                            ;
                            
                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$subOption] = [
                                'value' => $subOption,
                                'label' => $subOption,
                                'selected' => $this->isOptionSelected($fGet, $subOption, DcaUtil::isFieldMultiple($field)),
                            ];
                        }
                    }
                }

                break;

            case 'text':
            default:
                $objOptions = $this->getFilterOptions($f);


                if ($objOptions && 0 < $objOptions->count()) {
                    $filter['type'] = 'select';
                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            continue;
                        }

                        if ($this->shouldBeSkipped($f . ' = "'. $objOptions->{$f} .'"')) {
                            continue;
                        }

                        $filter['options'][] = [
                            'value' => $objOptions->{$f},
                            'label' => $objOptions->{$f},
                            'selected' => $this->isOptionSelected($fGet, $objOptions->{$f}, DcaUtil::isFieldMultiple($field)),
                        ];
                    }
                }

                break;
        }

        if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
            return;
        }

        if (null !== Input::get($fName) && '' !== Input::get($fName)) {
            $this->config[$f] = Input::get($fName);
        }

        $this->filters[] = $filter;
    }

    protected function isOptionSelected(string $f, string|int $v, bool $multiple = false) {
        return $multiple
            ? (null !== Input::get($f) && \in_array((string) $v, Input::get($f ?? []), true))
            : (null !== Input::get($f) && Input::get($f) === (string) $v)
        ;
    }

    // Add fulltext search if asked
    protected function addSearchFilter(): void
    {
        if ($this->model->wem_geodata_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'geodata_filter_search',
                'label' => $GLOBALS['TL_LANG']['WEM']['GEODATA']['search'] ?? 'Search',
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['GEODATA']['searchPlaceholder'] ?? 'Search',
                'value' => Input::get('geodata_filter_search') ?: '',
            ];

            if ('' !== Input::get('geodata_filter_search') && null !== Input::get('geodata_filter_search')) {
                $this->config['geodata_filter_search'] = StringUtil::formatKeywords(Input::get('geodata_filter_search'));
            }
        }
    }

    protected function shouldBeSkipped($statement): bool
    {
        if (!$this->model->wem_geodata_hideFiltersWithNoResults) {
            return false;
        }

        $config = $this->config;
        $config['where'][] = $statement;

        return 0 === $this->countItems($config);
    }
}
