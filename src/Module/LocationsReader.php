<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2023 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\Module;

use Contao\BackendTemplate;
use Contao\Combiner;
use Contao\Config;
use Contao\Environment;
use Contao\Input;
use Contao\System;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Front end module "locations reader".
 */
class LocationsReader extends Core
{
    /**
     * Map Template.
     *
     * @var string
     */
    protected $strTemplate = 'mod_wem_geodata_reader';

    /**
     * Display a wildcard in the back end.
     *
     * @return string
     */
    public function generate()
    {
        if (TL_MODE === 'BE') {
            $objTemplate = new BackendTemplate('be_wildcard');

            $objTemplate->wildcard = '### '.utf8_strtoupper($GLOBALS['TL_LANG']['FMD']['wem_display_map'][0]).' ###';
            $objTemplate->title = $this->headline;
            $objTemplate->id = $this->id;
            $objTemplate->link = $this->name;
            $objTemplate->href = 'contao/main.php?do=themes&amp;table=tl_module&amp;act=edit&amp;id='.$this->id;

            return $objTemplate->parse();
        }

        // Set the item from the auto_item parameter
        if (!isset($_GET['items']) && Config::get('useAutoItem') && isset($_GET['auto_item'])) {
            Input::setGet('items', Input::get('auto_item'));
        }

        // Return an empty string if "items" is not set (to combine list and reader on same page)
        if (!Input::get('items')) {
            return '';
        }

        return parent::generate();
    }

    /**
     * Generate the module.
     */
    protected function compile(): void
    {
        try {
            /* @var PageModel $objPage */
            global $objPage;

            $this->Template->articles = '';
            $this->Template->referer = 'javascript:history.go(-1)';
            $this->Template->back = $GLOBALS['TL_LANG']['MSC']['goBack'];

            // Get the location item
            $objItem = MapItem::findByIdOrAlias(Input::get('items'));

            // The location item does not exist or has an external target (see #33)
            if (null === $objItem || !$objItem->isPublishedForTimestamp()) {
                throw new \Exception(sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['pageNotFound'], Environment::get('uri')));
            }

            $arrItem = $this->getLocation($objItem);
            $objMap = Map::findByPk($objItem->pid);
            $this->Template->item = $arrItem;
            $this->Template->map = $objMap->row();
            $this->Template->shouldBeIndexed = $objMap->row();

            // Load the libraries
            $strVersion = 1;
            $objCssCombiner = new Combiner();
            $objCssCombiner->add('bundles/wemgeodata/css/default.css', $strVersion);
            $objCssCombiner->add('bundles/wemgeodata/css/leaflet.css', $strVersion);
            $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="https://unpkg.com/leaflet@latest/dist/leaflet.css">';
            $GLOBALS['TL_JAVASCRIPT'][] = 'https://unpkg.com/leaflet@latest/dist/leaflet.js';
            $GLOBALS['TL_HEAD'][] = '<link rel="stylesheet" href="https://unpkg.com/leaflet-gesture-handling@latest/dist/leaflet-gesture-handling.min.css">';
            $GLOBALS['TL_JAVASCRIPT'][] = 'https://unpkg.com/leaflet-gesture-handling@latest/dist/leaflet-gesture-handling.min.js';
            // And add them to pages
            $GLOBALS['TL_HEAD'][] = sprintf('<link rel="stylesheet" href="%s">', $objCssCombiner->getCombinedFile());

            // Override page details
            $htmlDecoder = null;
            try {
                $htmlDecoder = System::getContainer()->get('contao.string.html_decoder');
            } catch (\Exception $e) {
                // service does not exists
            }

            $responseContextAccessor = null;
            try {
                $responseContextAccessor = System::getContainer()->get('contao.routing.response_context_accessor'); // throws Exception if not found
            } catch (\Exception $e) {
                // service does not exists
            }

            $responseContext = $responseContextAccessor ? $responseContextAccessor->getResponseContext() : null;
            if ($responseContext && $responseContext->has(\Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag::class)) {
                /** @var HtmlHeadBag $htmlHeadBag */
                $htmlHeadBag = $responseContext->get(\Contao\CoreBundle\Routing\ResponseContext\HtmlHeadBag\HtmlHeadBag::class);
                $htmlHeadBag->setTitle($arrItem['title']);
                if ($htmlDecoder) { // exists in Contao 4.13 and is Contao 5.0 ready
                    $htmlHeadBag->setMetaDescription($htmlDecoder->htmlToPlainText($arrItem['content']));
                } elseif (method_exists(\Contao\StringUtil::class, 'htmlToPlainText')) { // exists in Contao 4.13 but is deprecated
                    $htmlHeadBag->setMetaDescription(\Contao\StringUtil::htmlToPlainText($arrItem['content']));
                } else { // pre Contao 4.13 behaviour
                    $htmlHeadBag->setMetaDescription($arrItem['content']);
                }
            } else {
                $objPage->ogTitle = $arrItem['title'];
                $objPage->pageTitle = $arrItem['title'];
                if ($htmlDecoder) { // exists in Contao 4.13 and is Contao 5.0 ready
                    $objPage->description = $htmlDecoder->htmlToPlainText($arrItem['content']);
                } elseif (method_exists(\Contao\StringUtil::class, 'htmlToPlainText')) { // exists in Contao 4.13 but is deprecated
                    $objPage->description = \Contao\StringUtil::htmlToPlainText($arrItem['content']);
                } else { // pre Contao 4.13 behaviour
                    $objPage->description = $arrItem['content'];
                }
            }
        } catch (\Exception $exception) {
            $this->Template->error = true;
            $this->Template->msg = $exception->getMessage();
        }
    }
}
