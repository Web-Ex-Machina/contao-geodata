<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2024 Web ex Machina
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
use Contao\CoreBundle\Intl\Locales;
use Contao\DataContainer;
use Contao\Environment;
use Contao\File;
use Contao\Input;
use Contao\Message;
use Contao\StringUtil;
use Contao\System;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\JsonResponse;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Controller\Provider\Nominatim;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;

/**
 * Provide backend functions to Locations Extension.
 */
class Callback extends Backend
{
    private Locales $locales;

    public function __construct(Locales $locales)
    {
        parent::__construct();
        // $this->locales = System::getContainer()->get('contao.intl.locales');
        $this->locales = $locales;
    }

    /**
     * Geocode a given location.
     * return JSON through AJAX request or Message with redirection.
     *
     * @param \DataContainer $objDc [Datacontainer to geocode]
     */
    public function geocode(DataContainer $objDc): ?string
    {
        $arrResponse = null;
        $objLocation = null;
        $objMap = null;

        if ('geocode' !== Input::get('key')) {
            return '';
        }

        try {
            $objLocation = MapItem::findByPk($objDc->id);
            $objMap = Map::findByPk($objLocation->pid);

            if (!$objMap->geocodingProvider) {
                throw new \Exception($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['missingConfigForGeocoding']);
            }

            switch ($objMap->geocodingProvider) {
                case 'gmaps': // hardcoded, as the constant doesn't exist anymore because we will not need it again
                    throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['featureDeletedIn'], 'Geocoding by Google', '2.0'));
                    break;
                case Map::GEOCODING_PROVIDER_NOMINATIM:
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
                $arrResponse = ['status' => 'success', 'response' => \sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'], $objLocation->title), 'data' => $arrCoords];
            } else {
                Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['CONFIRM']['locationSaved'], $objLocation->title));
            }
        } catch (\Exception $exception) {
            if ('ajax' === Input::get('src')) {
                $arrResponse = ['status' => 'error', 'response' => $exception->getMessage()];
            } else {
                Message::addError($exception->getMessage());
            }
        }

        if ('ajax' === Input::get('src')) {
            $objResponse = new JsonResponse($arrResponse);
            $objResponse->send();
        }

        $strRedirect = str_replace(['&key=geocode', 'id='.$objLocation->id, '&src=ajax'], ['', 'id='.$objMap->id, ''], Environment::get('request'));
        $this->redirect(StringUtil::ampersand($strRedirect));
    }

    /**
     * Return a form to choose a CSV file and import it.
     *
     * @throws \Exception
     */
    public function importLocations(): string
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
        foreach (StringUtil::deserialize($objMap->excelPattern) as $arrColumn) {
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
                    static::importStatic($callback[0])->{$callback[1]}($arrUploaded, $arrExcelPattern, $updateExistingItems, $deleteExistingItems, $objMap, $this);

                    System::setCookie('BE_PAGE_OFFSET', 0, 0);
                    $this->reload();
                }
            }

            foreach ($arrUploaded as $strFile) {
                $objFile = new File($strFile, true);
                $spreadsheet = IOFactory::load(TL_ROOT.'/'.$objFile->path);
                $sheetData = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
                $arrLocations = [];
                $nbRow = 0;
                foreach ($sheetData as $arrRow) {
                    if ([] === array_filter($arrRow)) {
                        continue;
                    }

                    ++$nbRow;
                    try {
                        $arrLocation = [];
                        $arrLocation['country'] = '';
                        $arrLocation['city'] = '';

                        foreach ($arrRow as $strColumn => $strValue) {
                            // strColumn = Excel Column
                            // strValue = Value in the current arrRow, at the column strColumn
                            $strValue = \is_string($strValue) ? trim($strValue) : $strValue;
                            switch ($arrExcelPattern[$strColumn]) {
                                case 'category':
                                    $objCategory = Category::findOneByTitle($strValue);

                                    if (!$objCategory) {
                                        break;
                                    }

                                    $arrLocation['category'] = $objCategory->id;
                                    break;
                                case 'region':
                                    if (null !== $strValue) {
                                        $arrLocation['admin_lvl_1'] = $strValue;
                                    }

                                    break;
                                case 'country':
                                    if (empty($strValue)) {
                                        throw new \Exception(\sprintf('Empty value for columns %s (%s)', $strColumn, $arrExcelPattern[$strColumn]));
                                    }

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
                        $arrLocations[$nbRow] = $arrLocation;
                    } catch (\Exception $e) {
                        Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['errorOnItemImport'], $nbRow, $e->getMessage()));
                        if (\array_key_exists($nbRow, $arrLocations)) {
                            unset($arrLocations[$nbRow]);
                        }
                    }
                }

                $intCreated = 0;
                $intUpdated = 0;
                $intDeleted = 0;
                $intErrors = 0;
                $arrNewLocations = [];

                foreach ($arrLocations as $k => $arrLocation) {
                    try {
                        $blnCreated = false;
                        $blnUpdated = false;
                        $arrLocation['alias'] = StringUtil::generateAlias($arrLocation['title'].'-'.$arrLocation['city'].'-'.$arrLocation['country'].'-'.($k + 1));

                        if ($updateExistingItems) {
                            $objLocation = MapItem::findItems(['alias' => $arrLocation['alias'], 'pid' => $objMap->id], 1);

                            // Create if don't exists
                            if (!$objLocation) {
                                $objLocation = new MapItem();
                                $objLocation->pid = $objMap->id;
                                $objLocation->published = 1;
                                ++$intCreated;
                                $blnCreated = true;
                            } else {
                                $objLocation = $objLocation->next();
                                ++$intUpdated;
                                $blnUpdated = true;
                            }
                        } else {
                            $objLocation = new MapItem();
                            $objLocation->pid = $objMap->id;
                            $objLocation->published = 1;
                            ++$intCreated;
                            $blnCreated = true;
                        }

                        $objLocation->tstamp = time();

                        foreach ($arrLocation as $strColumn => $varValue) {
                            $objLocation->$strColumn = $varValue;
                        }

                        $objLocation->save();
                        $arrNewLocations[] = $objLocation->id;
                    } catch (\Exception $e) {
                        ++$intErrors;
                        if ($blnCreated) {
                            --$intCreated;
                        } elseif ($blnUpdated) {
                            --$intUpdated;
                        }

                        Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['errorOnItemImport'], $objLocation->title, $e->getMessage()));
                    }
                }

                if ($deleteExistingItems) {
                    $objLocations = MapItem::findItems(['pid' => $objMap->id, 'published' => 1]);
                    while ($objLocations->next()) {
                        if (!\in_array($objLocations->id, $arrNewLocations, true)) {
                            $objLocations->delete();
                            ++$intDeleted;
                        }
                    }
                }
            }

            if (isset($intCreated)) {
                Message::addConfirmation(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['createdConfirmation'], $intCreated));
            }
            if (isset($intUpdated)) {
                Message::addInfo(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['updatedConfirmation'], $intUpdated));
            }
            if (isset($intDeleted)) {
                Message::addInfo(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['deletedConfirmation'], $intDeleted));
            }
            if (isset($intErrors)) {
                Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['errorsConfirmation'], $intErrors));
            }

            System::setCookie('BE_PAGE_OFFSET', 0, 0);
            $this->reload();
        }

        // Build an Excel pattern to show
        $arrTh = [];
        $arrTd = [];
        ksort($arrExcelPattern);
        foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
            $strDbColumn = 'region' === $strDbColumn ? 'admin_lvl_1' : $strDbColumn;
            $arrTh[] = '<th>'.$strExcelColumn.'</th>';
            $arrTd[0][] = '<td>'.$GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0].'</td>';
            $arrTd[1][] = '<td>'.$GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0].'</td>';
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE'])) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE'] as $callback) {
                [$arrTh, $arrTd] = static::importStatic($callback[0])->{$callback[1]}($arrTh, $arrTd, $arrExcelPattern, $objMap, $this);
            }
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

        $arrLanguages = $this->locales->getLanguages();

        /** @todo : provide an example file to download */
        $objTemplate = new BackendTemplate('be_wem_geodata_import_form');

        $objTemplate->backButtonHref = StringUtil::ampersand(str_replace('&key=import', '', Environment::get('request')));
        $objTemplate->backButtonTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];

        $objTemplate->downloadSampleButtonHref = StringUtil::ampersand(str_replace('&key=import', '&key=download_import_sample', Environment::get('request')));
        $objTemplate->downloadSampleButtonTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['tl_wem_map_item']['downloadSampleBTTitle']);
        $objTemplate->downloadSampleButtonLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['downloadSampleBT'];

        $objTemplate->formAction = StringUtil::ampersand(Environment::get('request'), true);
        $objTemplate->widgetUploadTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['source'][0];
        $objTemplate->widgetUploadContent = $objUploader->generateMarkup();
        $objTemplate->widgetUploadHelp = $GLOBALS['TL_LANG']['tl_wem_map_item']['source'][1] ?? '';
        $objTemplate->widgetSettingsTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['importSettingsTitle'];
        $objTemplate->widgetSettingsUpdateLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['importSettingsUpdateLabel'];
        $objTemplate->widgetSettingsUpdateChecked = (bool) $objMap->updateExistingItems;
        $objTemplate->widgetSettingsDeleteLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['importSettingsDeleteLabel'];
        $objTemplate->widgetSettingsDeleteChecked = (bool) $objMap->deleteExistingItemsNotInImportFile;
        $objTemplate->formSubmitValue = StringUtil::specialchars($GLOBALS['TL_LANG']['tl_wem_map_item']['import'][0]);
        $objTemplate->importExampleTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['importExampleTitle'];
        $objTemplate->importExampleTh = implode('', $arrTh);
        $objTemplate->importExampleTd = $arrTd;
        $objTemplate->importListCountriesTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['importListCountriesTitle'];
        $objTemplate->importListCountriesNameCurrentLanguage = $arrLanguages[$GLOBALS['TL_LANGUAGE']];
        $objTemplate->importListCountriesNameEnglish = $arrLanguages['en'];
        $objTemplate->importListCountries = $strCountries;
        $objTemplate->formRequestToken = REQUEST_TOKEN;
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function downloadImportSample()
    {
        if ('download_import_sample' !== Input::get('key')) {
            return '';
        }

        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findByPk(Input::get('id'));

        if (!$objMap) {
            return '';
        }

        // Generate the spreadsheet
        $objSpreadsheet = new Spreadsheet();
        $objSheet = $objSpreadsheet->getActiveSheet();

        $arrExcelPattern = [];
        // Preformat Excel Pattern (key = Excel column, value = DB Column)
        foreach (StringUtil::deserialize($objMap->excelPattern) as $arrColumn) {
            $arrExcelPattern[$arrColumn['value']] = $arrColumn['key'];
        }

        foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
            $strDbColumn = 'region' === $strDbColumn ? 'admin_lvl_1' : $strDbColumn;
            $objSheet->setCellValue($strExcelColumn.'1', $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0]);
            $objSheet->setCellValue($strExcelColumn.'2', $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0]);
        }

        // And send to browser
        $strFilename = date('Y-m-d_H-i').'_import-locations-sample';
        $format = IOFactory::WRITER_XLSX;

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE'])) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE'] as $callback) {
                $objSpreadsheetTemp = static::importStatic($callback[0])->{$callback[1]}($objSpreadsheet, $arrExcelPattern, $objMap, $this);
                if ($objSpreadsheetTemp) {
                    $objSpreadsheet = $objSpreadsheetTemp;
                }
            }
        }

        header('Content-Disposition: attachment;filename="'.$strFilename.'.xlsx"');

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($objSpreadsheet, $format);
        $writer->save('php://output');
        exit;
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     *
     * @throws \Exception
     */
    public function exportLocationsForm(): string
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

        $arrCountriesSystem = Util::getCountries();
        $arrCountries = [];
        $items = MapItem::findItems(['pid' => $objMap->id]);
        if ($items) {
            while ($items->next()) {
                $arrCountries[$items->country] = $arrCountriesSystem[strtoupper($items->country)] ?? $arrCountriesSystem[strtolower($items->country)];
            }
        }

        $objTemplate = new BackendTemplate('be_wem_geodata_export_form');

        $objTemplate->backButtonHref = StringUtil::ampersand(str_replace('&key=export_form', '', Environment::get('request')));
        $objTemplate->backButtonTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $objTemplate->formAction = StringUtil::ampersand(str_replace('key=export_form', 'key=export', Environment::get('request')), true);

        $objTemplate->widgetSettingsTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsTitle'];
        $objTemplate->widgetSettingsFormatLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsFormatLabel'];

        $objTemplate->widgetSettingsLimitToCategoriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCategoriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCategoriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCategoriesSelectLabel'];
        $objTemplate->widgetSettingsLimitToCountriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCountriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCountriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCountriesSelectLabel'];
        $objTemplate->formSubmitValue = StringUtil::specialchars($GLOBALS['TL_LANG']['tl_wem_map_item']['export_form'][0]);

        $objTemplate->categories = $arrCategories;
        $objTemplate->countries = $arrCountries;
        $objTemplate->formRequestToken = REQUEST_TOKEN;
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     *
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \Exception
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
            $params['where'][] = \sprintf('category IN (%s)', implode(',', Input::post('limit_to_categories')));
        }

        if (Input::post('chk_limit_to_countries')) {
            $params['where'][] = \sprintf('country IN ("%s")', implode('","', Input::post('limit_to_countries')));
        }

        $arrExcelPattern = [];
        // Preformat Excel Pattern (key = DB Column, value = Excel column)
        foreach (StringUtil::deserialize($objMap->excelPattern) as $arrColumn) {
            $arrExcelPattern[$arrColumn['key']] = $arrColumn['value'];
        }

        // Fetch all the locations
        $arrCountries = Util::getCountries();
        $objLocations = MapItem::findItems($params);

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
            $arrRow = null;
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
            if (null !== $arrRow) {
                $arrRows[] = $arrRow;
            }
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
        switch (strtolower(Input::post('format') ?? '')) {
            case 'csv':
                $format = IOFactory::WRITER_CSV;
                header('Content-Disposition: attachment;filename="'.$strFilename.'.csv"');
                break;
            case 'xlsx':
                $format = IOFactory::WRITER_XLSX;
                header('Content-Disposition: attachment;filename="'.$strFilename.'.xlsx"');
                break;
            default:
                throw new \Exception(\sprintf($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['unknownExportFormat'], implode('","', ['csv', 'xlsx'])));
        }

        // HOOK: add custom logic
        if (isset($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT']) && \is_array($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT'])) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT'] as $callback) {
                $objSpreadsheetTemp = static::importStatic($callback[0])->{$callback[1]}($objSpreadsheet, $arrExcelPattern, $objLocations->reset(), $arrCountries, $objMap, $format, $this);
                if ($objSpreadsheetTemp) {
                    $objSpreadsheet = $objSpreadsheetTemp;
                }
            }
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($objSpreadsheet, $format);

        if (IOFactory::WRITER_CSV === $format) {
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
        }

        $writer->save('php://output');
        exit;
    }

    /**
     * @throws \Exception
     */
    public function copyMapItem(DataContainer $dc): void
    {
        if (!$dc->id) {
            return;
        }

        $objMapOld = Map::findByPk($dc->id);
        if (!$objMapOld) {
            throw new \Exception(\sprintf('Unable to find map %s', $dc->id));
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
