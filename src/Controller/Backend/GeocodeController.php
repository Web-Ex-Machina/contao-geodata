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
use Contao\DataContainer;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Service\Nominatim;

/**
 * Provide backend functions to Locations Extension.
 */
class GeocodeController extends AbstractController
{
    public function __construct(
        private readonly Nominatim $nominatim,
    ) {
    }

    /**
     * Geocode a given location. return JSON through AJAX request or Message
     * with redirection.
     *
     * @param DataContainer $objDc [Datacontainer to geocode]
     */
    public function run(DataContainer $objDc): string|null 
    {
        $arrResponse = null;
        $objLocation = null;
        $objMap = null;
        $isAjax = 'ajax' === Input::get('src');

        if ('geocode' !== Input::get('key')) {
            return '';
        }

        try {
            $objLocation = MapItem::findById($objDc->id);
            $objMap = Map::findById($objLocation->pid);

            if (!$objMap->geocodingProvider) {
                throw new Exception(
                    $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']
                );
            }

            switch ($objMap->geocodingProvider) {
                case Map::GEOCODING_PROVIDER_NOMINATIM:
                    $arrCoords = $this->nominatim->geocode($objLocation, $objMap);
                    break;
                default:
                    throw new Exception(
                        $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']
                    );
            }

            $objLocation->lat = $arrCoords['lat'];
            $objLocation->lng = $arrCoords['lng'];

            if (!$objLocation->save()) {
                throw new Exception(
                    $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['errorWhenSavingTheLocation']
                );
            }

            if ($isAjax) {
                $arrResponse = [
                    'status' => 'success',
                    'response' => \sprintf(
                        $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'],
                        $objLocation->title
                    ),
                    'data' => $arrCoords,
                ];
            } else {
                Message::addConfirmation(
                    \sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'], $objLocation->title)
                );
            }
        } catch (Exception $exception) {
            if ($isAjax) {
                $arrResponse = [
                    'status' => 'error',
                    'response' => $exception->getMessage(),
                ];
            } else {
                Message::addError($exception->getMessage());
            }
        }

        if ($isAjax) {
            $objResponse = new JsonResponse($arrResponse);
            $objResponse->send();
        }

        $strRedirect = str_replace(
            ['&key=geocode', 'id=' . $objLocation->id, '&src=ajax'],
            ['', 'id=' . $objMap->id, ''],
            Environment::get('request'),
        );

        \Contao\Controller::redirect(StringUtil::ampersand($strRedirect));
    }
}