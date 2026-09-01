<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\ContaoPageSchema;
use Contao\CoreBundle\Routing\ResponseContext\JsonLd\JsonLdManager;
use Contao\CoreBundle\Util\UrlUtil;
use Contao\Environment;
use Contao\Input;
use Contao\ModuleModel;
use Contao\PageModel;
use Contao\Template;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\UX\Map\Map;
use Symfony\UX\Map\Marker;
use Symfony\UX\Map\InfoWindow;
use Symfony\UX\Map\Point;
use Symfony\UX\Map\Bridge\Leaflet\LeafletOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\AttributionControlOptions;
use Symfony\UX\Map\Bridge\Leaflet\Option\ControlPosition;
use Symfony\UX\Map\Bridge\Leaflet\Option\TileLayer;
use Symfony\UX\Map\Bridge\Leaflet\Option\ZoomControlOptions;
use WEM\GeoDataBundle\Model\MapItem;

#[AsFrontendModule(
    ReaderController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_reader'
)]
class ReaderController extends ModuleController
{
    public const TYPE = 'wem_geodata_reader';
    protected MapItem $mapitem;

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator,
    ) {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $this->loadMap();

        $this->mapitem = $this->findItem();

        if (!$this->mapitem) {
            throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
        }

        if ($this->model->overviewPage && ($overviewPage = PageModel::findById($this->model->overviewPage))) {
            $template->referer = $this->contentUrlGenerator->generate($overviewPage);
            $template->back = $this->model->customLabel ?: $GLOBALS['TL_LANG']['MSC']['newsOverview'];
        }

        // Overwrite the page metadata
        $responseContext = System::getContainer()->get('contao.routing.response_context_accessor')->getResponseContext();

        if ($responseContext?->has(HtmlHeadBag::class)) {
            $htmlHeadBag = $responseContext->get(HtmlHeadBag::class);
            $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');

            if ($this->mapitem->pageTitle) {
                $htmlHeadBag->setTitle($this->mapitem->pageTitle);
            } elseif ($this->mapitem->title) {
                $htmlHeadBag->setTitle($this->mapitem->title);
            }

            if ($this->mapitem->description) {
                $htmlHeadBag->setMetaDescription($htmlDecoder->inputEncodedToPlainText($this->mapitem->description));
            } elseif ($this->mapitem->teaser) {
                $htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($this->mapitem->teaser));
            }

            if ($this->mapitem->robots) {
                $htmlHeadBag->setMetaRobots($this->mapitem->robots);
            }

            if ($this->mapitem->canonicalLink) {
                $url = System::getContainer()->get('contao.insert_tag.parser')->replaceInline($this->mapitem->canonicalLink);

                // Ensure absolute links
                if (!preg_match('#^https?://#', $url)) {
                    if (!$request = System::getContainer()->get('request_stack')->getCurrentRequest()) {
                        throw new \RuntimeException('The request stack did not contain a request');
                    }

                    $url = UrlUtil::makeAbsolute($url, $request->getUri());
                }

                $htmlHeadBag->setCanonicalUri($url);
            }
        }

        // Update the JSON+LD "searchIndexer" setting
        $pageSchema = $responseContext->get(JsonLdManager::class)->getGraphForSchema(JsonLdManager::SCHEMA_CONTAO)->get(ContaoPageSchema::class);

        if ($this->mapitem->searchIndexer) {
            $pageSchema['searchIndexer'] = $this->mapitem->searchIndexer;
        }

        // Add the articles
        $template->item = $this->parseItem($this->mapitem);
        $template->moduleId = $this->model->id;

        $template->mapTem = $this->getmap();

        return $template->getResponse();
    }

    protected function getmap()
    {
        $map = new Map();
        $map
            // Explicitly set the center and zoom
            ->center(new Point(47.903354, 1.888334))
            ->zoom(6)

            ->addMarker(new Marker(
                position: new Point(45.7640, 4.8357),
                title: 'Lyon',
                infoWindow: new InfoWindow(
                    headerContent: '<b>Lyon</b>',
                    content: 'The French town in the historic Rhône-Alpes region, located at the junction of the Rhône and Saône rivers.'
                ),
            ))

            // Or automatically fit the bounds to the markers
            ->fitBoundsToMarkers()
        ;

        $leafletOptions = (new LeafletOptions())
            ->tileLayer(new TileLayer(
                url: 'https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
                options: [
                    'minZoom' => 5,
                    'maxZoom' => 10,
                ]
            ))
            ->attributionControl(false)
            ->attributionControlOptions(new AttributionControlOptions(ControlPosition::BOTTOM_LEFT))
            ->zoomControl(false)
            ->zoomControlOptions(new ZoomControlOptions(ControlPosition::TOP_LEFT))
        ;

        $map->options($leafletOptions);

        $t = new \Contao\FrontendTemplate('map_simple');
        $t->map = $map;
        return $t->parse();
    }

    protected function findItem(): ?MapItem
    {
        $item = MapItem::findByIdOrAlias(Input::get('auto_item'));

        if ($item) {
            return $item;
        }

        return null;
    }
}
