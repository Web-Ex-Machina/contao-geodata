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

namespace WEM\GeoDataBundle\Backend;

use WEM\GeoDataBundle\Controller\Provider\GoogleMaps;
use WEM\GeoDataBundle\Controller\Provider\Nominatim;
use WEM\GeoDataBundle\Controller\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Item;
use WEM\GeoDataBundle\Model\Map;
use Contao\Backend;
use Contao\StringUtil;
use Contao\DataContainer;
use Contao\Input;
use Contao\Message;
use Contao\Environment;
use Contao\System;
use Contao\File;
use Contao\Config;
use Haste\Http\Response\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Provide backend functions to Locations Extension.
 */
class Callback extends Backend
{
    /**
     * Geocode a given location.
     *
     * @param \DataContainer $objDc [Datacontainer to geocode]
     *
     * @return JSON through AJAX request or Message with redirection
     */
    public function geocode(DataContainer $objDc)
    {
        if ('geocode' !== Input::get('key')) {
            return '';
        }

        try {
            $objLocation = Item::findByPk($objDc->id);
            $objMap = Map::findByPk($objLocation->pid);

            if (!$objMap->geocodingProvider) {
                throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']);
            }
            switch ($objMap->geocodingProvider) {
                case 'gmaps':
                    $arrCoords = GoogleMaps::geocoder($objLocation, $objMap);
                break;
                case 'nominatim':
                    $arrCoords = Nominatim::geocoder($objLocation, $objMap);
                break;
                default:
                    throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']);
            }

            $objLocation->lat = $arrCoords['lat'];
            $objLocation->lng = $arrCoords['lng'];

            if (!$objLocation->save()) {
                throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['errorWhenSavingTheLocation']);
            }
            if ('ajax' === Input::get('src')) {
                $arrResponse = ['status' => 'success', 'response' => sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'], $objLocation->title), 'data' => $arrCoords];
            } else {
                Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'], $objLocation->title));
            }
        } catch (\Exception $e) {
            if ('ajax' === Input::get('src')) {
                $arrResponse = ['status' => 'error', 'response' => $e->getMessage()];
            } else {
                Message::addError($e->getMessage());
            }
        }

        if ('ajax' === Input::get('src')) {
            $objResponse = new JsonResponse($arrResponse);
            $objResponse->send();
        }

        $strRedirect = str_replace(['&key=geocode', 'id='.$objLocation->id, '&src=ajax'], ['', 'id='.$objMap->id, ''], Environment::get('request'));
        $this->redirect(ampersand($strRedirect));
    }

    /**
     * Return a form to choose a CSV file and import it.
     *
     * @return string
     */
    public function importLocations()
    {
        if ('import' !== Input::get('key')) {
            return '';
        }

        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findByPk(Input::get('id'));

        $this->import('BackendUser', 'User');
        $class = $this->User->uploader;

        // See #4086 and #7046
        if (!class_exists($class) || 'DropZone' === $class) {
            $class = 'FileUpload';
        }

        /** @var \FileUpload $objUploader */
        $objUploader = new $class();

        $arrExcelPattern = [];
        // Preformat Excel Pattern (key = Excel column, value = DB Column)
        foreach (deserialize($objMap->excelPattern) as $arrColumn) {
            $arrExcelPattern[$arrColumn['value']] = $arrColumn['key'];
        }

        // Import CSS
        if ('tl_wem_items_import' === Input::post('FORM_SUBMIT')) {
            $arrUploaded = $objUploader->uploadTo('system/tmp');
            if (empty($arrUploaded)) {
                Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }

            // HOOK: add custom logic
            if (isset($GLOBALS['TL_HOOKS']['ALTRADMAPIMPORTLOCATIONS']) && \is_array($GLOBALS['TL_HOOKS']['ALTRADMAPIMPORTLOCATIONS']))
            {
                foreach ($GLOBALS['TL_HOOKS']['ALTRADMAPIMPORTLOCATIONS'] as $callback)
                {
                    static::importStatic($callback[0])->{$callback[1]}($arrUploaded, $arrExcelPattern, $objMap, $this);

                    System::setCookie('BE_PAGE_OFFSET', 0, 0);
                    $this->reload();
                }
            }

            $time = time();
            $intTotal = 0;
            $intInvalid = 0;

            foreach ($arrUploaded as $strFile) {
                $objFile = new File($strFile, true);
                $spreadsheet = IOFactory::load(TL_ROOT.'/'.$objFile->path);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                $arrLocations = [];

                foreach ($sheetData as $arrRow) {
                    $arrLocation = [];
                    $arrLocation['country'] = '';
                    $arrLocation['city'] = '';

                    foreach ($arrRow as $strColumn => $strValue) {
                        // strColumn = Excel Column
                        // strValue = Value in the current arrRow, at the column strColumn
                        switch ($arrExcelPattern[$strColumn]) {
                            case 'category':
                                $objCategory = Category::findOneByTitle($strValue);

                                if (!$objCategory) {
                                    continue;
                                }

                                $arrLocation['category'] = $objCategory->id;
                            break;
                            case 'country':
                                if(2 === strlen($strValue)) {
                                    $arrLocation['country'] = $strValue;
                                } else {
                                    $arrLocation['country'] = Util::getCountryISOCodeFromFullname($strValue);
                                }                                
                            break;
                            default:
                                if(null === $strValue) {
                                    continue;
                                }

                                $arrLocation[$arrExcelPattern[$strColumn]] = $strValue;
                        }
                    }

                    $arrLocation['continent'] = Util::getCountryContinent($arrLocation['country']);
                    $arrLocations[] = $arrLocation;
                }

                $intCreated = 0;
                $intUpdated = 0;
                $intDeleted = 0;
                $arrNewLocations = [];

                foreach ($arrLocations as $k => $arrLocation) {
                    $arrLocation['alias'] = StringUtil::generateAlias($arrLocation['title'].'-'.$arrLocation['city'].'-'.$arrLocation['country'].'-'.($k+1));

                    $objLocation = Item::findOneBy('alias', $arrLocation['alias']);

                    // Create if don't exists
                    if (!$objLocation) {
                        $objLocation = new Item();
                        $objLocation->pid = $objMap->id;
                        $objLocation->published = 1;
                        ++$intCreated;
                    } else {
                        ++$intUpdated;
                    }

                    $objLocation->tstamp = time();

                    foreach ($arrLocation as $strColumn => $varValue) {
                        $objLocation->$strColumn = $varValue;
                    }

                    $objLocation->save();
                    $arrNewLocations[] = $objLocation->id;
                }

                $objLocations = Item::findItems(['pid' => $objMap->id, 'published' => 1]);
                while ($objLocations->next()) {
                    if (!\in_array($objLocations->id, $arrNewLocations, true)) {
                        $objLocations->delete();
                        ++$intDeleted;
                    }
                }
            }

            Message::addConfirmation(sprintf($GLOBALS['TL_LANG']['tl_wem_item']['createdConfirmation'], $intCreated));
            Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_wem_item']['updatedConfirmation'], $intUpdated));
            Message::addInfo(sprintf($GLOBALS['TL_LANG']['tl_wem_item']['deletedConfirmation'], $intDeleted));

            System::setCookie('BE_PAGE_OFFSET', 0, 0);
            $this->reload();
        }

        // Build an Excel pattern to show
        $arrTh = [];
        $arrTd = [];
        foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
            $arrTh[] = '<th>'.$strExcelColumn.'</th>';
            $arrTd[] = '<td>'.$GLOBALS['TL_LANG']['tl_wem_item'][$strDbColumn][0].'</td>';
        }

        // Build the country array, to give the correct syntax to users
        $arrCountries = [];
        System::loadLanguageFile('countries');
        foreach ($GLOBALS['TL_LANG']['CNT'] as $strIsoCode => $strName) {
            $arrCountries[$strIsoCode]['current'] = $strName;
        }

        System::loadLanguageFile('countries', 'en');
        foreach ($GLOBALS['TL_LANG']['CNT'] as $strIsoCode => $strName) {
            $arrCountries[$strIsoCode]['en'] = $strName;
        }

        $strCountries = '';
        foreach ($arrCountries as $strIsoCode => $arrNames) {
            $strCountries .= '<tr>';
            $strCountries .= '<td>'.$strIsoCode.'</td>';
            $strCountries .= '<td>'.$arrNames['current'].'</td>';
            $strCountries .= '<td>'.$arrNames['en'].'</td>';
            $strCountries .= '</tr>';
        }

        $arrLanguages = System::getLanguages();

        // Return form
        return '
        <div id="tl_buttons">
        <a href="'.ampersand(str_replace('&key=import', '', Environment::get('request'))).'" class="header_back" title="'.specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']).'" accesskey="b">'.$GLOBALS['TL_LANG']['MSC']['backBT'].'</a>
        </div>
        '.Message::generate().'
        <form action="'.ampersand(Environment::get('request'), true).'" id="tl_wem_items_import" class="tl_form" method="post" enctype="multipart/form-data">
        <div class="tl_formbody_edit">
        <input type="hidden" name="FORM_SUBMIT" value="tl_wem_items_import">
        <input type="hidden" name="REQUEST_TOKEN" value="'.REQUEST_TOKEN.'">
        <input type="hidden" name="MAX_FILE_SIZE" value="'.Config::get('maxFileSize').'">

        <fieldset class="tl_tbox nolegend">
            <div class="widget">
              <h3>'.$GLOBALS['TL_LANG']['tl_wem_item']['source'][0].'</h3>'.$objUploader->generateMarkup().(isset($GLOBALS['TL_LANG']['tl_wem_item']['source'][1]) ? '
              <p class="tl_help tl_tip">'.$GLOBALS['TL_LANG']['tl_wem_item']['source'][1].'</p>' : '').'
            </div>
        </div>
        </fieldset>

        <div class="tl_formbody_submit">
            <div class="tl_submit_container">
              <input type="submit" name="save" id="save" class="tl_submit" accesskey="s" value="'.specialchars($GLOBALS['TL_LANG']['tl_wem_item']['import'][0]).'">
            </div>
        </div>

        <fieldset class="tl_tbox nolegend">
            <div class="widget">
            <h3>'.$GLOBALS['TL_LANG']['tl_wem_item']['importExampleTitle'].'</h3>
            <table class="wem_locations_import_table">
                <thead>
                    <tr>'.implode('', $arrTh).'</tr>
                </thead>
                <tbody>
                    <tr>'.implode('', $arrTd).'</tr>
                    <tr>'.implode('', $arrTd).'</tr>
                </tbody>
            </table>
            </div>
        </fieldset>

        <fieldset class="tl_tbox nolegend">
            <div class="widget">
            <h3>'.$GLOBALS['TL_LANG']['tl_wem_item']['importListCountriesTitle'].'</h3>
            <table class="wem_locations_import_table">
                <thead>
                    <tr><th>ISOCode</th><th>'.$arrLanguages[$GLOBALS['TL_LANGUAGE']].'</th><th>'.$arrLanguages['en'].'</th></tr>
                </thead>
                <tbody>
                    '.$strCountries.'
                </tbody>
            </table>
            </div>
        </fieldset>

        </form>';
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     */
    public function exportLocations()
    {
        if ('export' !== Input::get('key')) {
            return '';
        }

        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findByPk(Input::get('id'));
        $arrExcelPattern = [];
        // Preformat Excel Pattern (key = DB Column, value = Excel column)
        foreach (deserialize($objMap->excelPattern) as $arrColumn) {
            $arrExcelPattern[$arrColumn['key']] = $arrColumn['value'];
        }

        // Fetch all the locations
        $arrCountries = System::getCountries();
        $objLocations = Item::findItems(['pid' => $objMap->id]);

        // Break if no locations
        if (!$objLocations) {
            Message::addError($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
            $this->reload();
        }

        // Format for the Excel
        $arrRows = [];
        while ($objLocations->next()) {
            foreach ($arrExcelPattern as $strDbColumn => $strExcelColumn) {
                switch ($strDbColumn) {
                    case 'country':
                        $arrRow[$strExcelColumn] = $arrCountries[$objLocations->$strDbColumn];
                    break;
                    default:
                        $arrRow[$strExcelColumn] = $objLocations->$strDbColumn;
                }
            }
            $arrRows[] = $arrRow;
        }

        // Generate the spreadsheet
        $objSpreadsheet = new Spreadsheet();
        $objSheet = $objSpreadsheet->getActiveSheet();

        // Fill the cells of the Excel
        foreach ($arrRows as $intRow => $arrRow) {
            foreach ($arrRow as $strColumn => $strValue) {
                $objSheet->setCellValue($strColumn.($intRow + 1), $strValue);
            }
        }

        // And send to browser
        $strFilename = date('Y-m-d_H-i').'_export-locations';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="'.$strFilename.'.xlsx"');
        header('Cache-Control: max-age=0');
        $writer = IOFactory::createWriter($objSpreadsheet, 'Xlsx');
        $writer->save('php://output');
        exit;
    }
}
