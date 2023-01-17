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

namespace WEM\GeoDataBundle\Backend;

use Contao\Backend;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\DataContainer;
use Contao\Environment;
use Contao\File;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use Exception;
use Haste\Http\Response\JsonResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use WEM\GeoDataBundle\Controller\Provider\GoogleMaps;
use WEM\GeoDataBundle\Controller\Provider\Nominatim;
use WEM\GeoDataBundle\Controller\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Item;
use WEM\GeoDataBundle\Model\Map;

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
            $updateExistingItems = (bool) Input::post('update_existing_items');
            $deleteExistingItems = (bool) Input::post('delete_existing_items_not_in_import_file');

            $arrUploaded = $objUploader->uploadTo('system/tmp');
            if (empty($arrUploaded)) {
                Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
                $this->reload();
            }

            // HOOK: add custom logic
            if (isset($GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS'])) {
                foreach ($GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS'] as $callback) {
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
                                    break;
                                }

                                $arrLocation['category'] = $objCategory->id;
                            break;
                            case 'region':
                                $arrLocation['admin_lvl_1'] = $strValue;
                            break;
                            case 'country':
                                if (2 === \strlen($strValue)) {
                                    $arrLocation['country'] = $strValue;
                                } else {
                                    $arrLocation['country'] = Util::getCountryISOCodeFromFullname($strValue);
                                }
                            break;
                            default:
                                if (null === $strValue) {
                                    break;
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
                    $arrLocation['alias'] = StringUtil::generateAlias($arrLocation['title'].'-'.$arrLocation['city'].'-'.$arrLocation['country'].'-'.($k + 1));

                    if ($updateExistingItems) {
                        $objLocation = Item::findItems(['alias' => $arrLocation['alias'], 'pid' => $objMap->id], 1);

                        // Create if don't exists
                        if (!$objLocation) {
                            $objLocation = new Item();
                            $objLocation->pid = $objMap->id;
                            $objLocation->published = 1;
                            ++$intCreated;
                        } else {
                            $objLocation = $objLocation->next();
                            ++$intUpdated;
                        }
                    } else {
                        $objLocation = new Item();
                        $objLocation->pid = $objMap->id;
                        $objLocation->published = 1;
                        ++$intCreated;
                    }

                    $objLocation->tstamp = time();

                    foreach ($arrLocation as $strColumn => $varValue) {
                        $objLocation->$strColumn = $varValue;
                    }

                    $objLocation->save();
                    $arrNewLocations[] = $objLocation->id;
                }

                if ($deleteExistingItems) {
                    $objLocations = Item::findItems(['pid' => $objMap->id, 'published' => 1]);
                    while ($objLocations->next()) {
                        if (!\in_array($objLocations->id, $arrNewLocations, true)) {
                            $objLocations->delete();
                            ++$intDeleted;
                        }
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
            $strDbColumn = 'region' === $strDbColumn ? 'admin_lvl_1' : $strDbColumn;
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

        /** @todo : provide an example file to download */
        $objTemplate = new BackendTemplate('be_wem_geodata_import_form');

        $objTemplate->backButtonHref = ampersand(str_replace('&key=import', '', Environment::get('request')));
        $objTemplate->backButtonTitle = specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $objTemplate->formAction = ampersand(Environment::get('request'), true);
        $objTemplate->widgetUploadTitle = $GLOBALS['TL_LANG']['tl_wem_item']['source'][0];
        $objTemplate->widgetUploadContent = $objUploader->generateMarkup();
        $objTemplate->widgetUploadHelp = $GLOBALS['TL_LANG']['tl_wem_item']['source'][1] ?? '';
        $objTemplate->widgetSettingsTitle = $GLOBALS['TL_LANG']['tl_wem_item']['importSettingsTitle'];
        $objTemplate->widgetSettingsUpdateLabel = $GLOBALS['TL_LANG']['tl_wem_item']['importSettingsUpdateLabel'];
        $objTemplate->widgetSettingsUpdateChecked = (bool) $objMap->updateExistingItems;
        $objTemplate->widgetSettingsDeleteLabel = $GLOBALS['TL_LANG']['tl_wem_item']['importSettingsDeleteLabel'];
        $objTemplate->widgetSettingsDeleteChecked = (bool) $objMap->deleteExistingItemsNotInImportFile;
        $objTemplate->formSubmitValue = specialchars($GLOBALS['TL_LANG']['tl_wem_item']['import'][0]);
        $objTemplate->importExampleTitle = $GLOBALS['TL_LANG']['tl_wem_item']['importExampleTitle'];
        $objTemplate->importExampleTh = implode('', $arrTh);
        $objTemplate->importExampleTd = implode('', $arrTd);
        $objTemplate->importListCountriesTitle = $GLOBALS['TL_LANG']['tl_wem_item']['importListCountriesTitle'];
        $objTemplate->importListCountriesNameCurrentLanguage = $arrLanguages[$GLOBALS['TL_LANGUAGE']];
        $objTemplate->importListCountriesNameEnglish = $arrLanguages['en'];
        $objTemplate->importListCountries = $strCountries;
        $objTemplate->formRequestToken = REQUEST_TOKEN;
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     */
    public function exportLocationsForm()
    {
        if ('export_form' !== Input::get('key')) {
            return '';
        }

        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findByPk(Input::get('id'));

        $arrCategories = [];
        $categories = Category::findItems(['pid' => $objMap->id]);
        if ($categories) {
            while ($categories->next()) {
                $arrCategories[$categories->id] = $categories->title;
            }
        }

        $arrCountriesSystem = System::getContainer()->get('contao.intl.countries')->getCountries();
        $arrCountries = [];
        $items = Item::findItems(['pid' => $objMap->id]);
        if ($items) {
            while ($items->next()) {
                $arrCountries[$items->country] = $arrCountriesSystem[strtoupper($items->country)];
            }
        }

        $objTemplate = new BackendTemplate('be_wem_geodata_export_form');

        $objTemplate->backButtonHref = ampersand(str_replace('&key=export_form', '', Environment::get('request')));
        $objTemplate->backButtonTitle = specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $objTemplate->formAction = ampersand(str_replace('key=export_form', 'key=export', Environment::get('request')), true);

        $objTemplate->widgetSettingsTitle = $GLOBALS['TL_LANG']['tl_wem_item']['exportSettingsTitle'];
        $objTemplate->widgetSettingsLimitToCategoriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_item']['exportSettingsLimitToCategoriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCategoriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_item']['exportSettingsLimitToCategoriesSelectLabel'];
        $objTemplate->widgetSettingsLimitToCountriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_item']['exportSettingsLimitToCountriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCountriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_item']['exportSettingsLimitToCountriesSelectLabel'];
        $objTemplate->formSubmitValue = specialchars($GLOBALS['TL_LANG']['tl_wem_item']['export'][0]);

        $objTemplate->categories = $arrCategories;
        $objTemplate->countries = $arrCountries;
        $objTemplate->formRequestToken = REQUEST_TOKEN;
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
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

        if (!$objMap) {
            return '';
        }

        $params = ['pid' => $objMap->id];
        if (Input::post('chk_limit_to_categories')) {
            $params['where'][] = sprintf('category IN (%s)', implode(',', Input::post('limit_to_categories')));
        }
        if (Input::post('chk_limit_to_countries')) {
            $params['where'][] = sprintf('country IN ("%s")', implode('","', Input::post('limit_to_countries')));
        }

        $arrExcelPattern = [];
        // Preformat Excel Pattern (key = DB Column, value = Excel column)
        foreach (deserialize($objMap->excelPattern) as $arrColumn) {
            $arrExcelPattern[$arrColumn['key']] = $arrColumn['value'];
        }

        // Fetch all the locations
        $arrCountries = System::getCountries();
        $objLocations = Item::findItems($params);

        // Break if no locations
        if (!$objLocations) {
            Message::addError($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
            $url = Environment::get('uri');
            $url = str_replace(['&key=export'], ['&key=export_form'], $url);

            $this->redirect($url);
        }

        // Format for the Excel
        $arrRows = [];
        while ($objLocations->next()) {
            foreach ($arrExcelPattern as $strDbColumn => $strExcelColumn) {
                switch ($strDbColumn) {
                    case 'country':
                        $arrRow[$strExcelColumn] = $arrCountries[$objLocations->$strDbColumn];
                    break;
                    case 'region':
                        $arrRow[$strExcelColumn] = $objLocations->admin_lvl_1;
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

    public function copyMapItem(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $objMapOld = Map::findByPk($dc->id);
        if (!$objMapOld) {
            throw new Exception(sprintf('Unable to find map %s', $dc->id));
        }

        $arrData = $objMapOld->row();
        unset($arrData['id']);

        $objMap = new Map();
        $objMap->setRow($arrData);
        // $objMap->id = null;
        $objMap->save();

        $url = Environment::get('uri');
        $url = str_replace(['&key=copy_map_item', '&id='.$dc->id], ['&act=edit', '&id='.$objMap->id], $url);

        $this->redirect($url);
    }
}
