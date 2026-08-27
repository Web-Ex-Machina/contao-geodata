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
use Contao\CoreBundle\Exception\ResponseException;
use Contao\CoreBundle\Intl\Countries;
use Contao\CoreBundle\Intl\Locales;
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\File;
use Contao\FileUpload;
use Contao\Input;
use Contao\Message;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\CountriesUtil;

/**
 * Provide backend functions to Locations Extension.
 */
class ImportController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly Countries $countries,
        private readonly Locales $locales,
    ) {
    }

    public function run(): string
    {
        $formId = 'tl_geodata_import';
        $request = $this->requestStack->getCurrentRequest();

        $objMap = Map::findById(Input::get('id'));

        if (!$objMap) {
            return '';
        }

        if ($request->request->get('FORM_SUBMIT') === $formId) {
            $this->import($request, $objMap);
        }

        return $this->getForm($formId, $objMap);
    }

    protected function getForm(string $formId, Map $objMap): string
    {
        if (!Input::get('id')) {
            return '';
        }    

        /** @var FileUpload $objUploader */
        $objUploader = new FileUpload();
        
        $arrExcelPattern = $objMap->getImportPattern();

        // Build an Excel pattern to show
        $arrTh = [];
        $arrTd = [];

        foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
            $strDbColumn = $strDbColumn === 'region' ? 'admin_lvl_1' : $strDbColumn;
            $arrTh[] = '<th>' . $strExcelColumn . '</th>';
            $arrTd[0][] = '<td>' . $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0] . '</td>';
            $arrTd[1][] = '<td>' . $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0] . '</td>';
        }

        // HOOK: add custom logic
        if (
            isset($GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE']) && \is_array(
                $GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE']
            )
        ) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADISPLAYLOCATIONSSAMPLE'] as $callback) {
                [$arrTh, $arrTd] = static::importStatic(
                    $callback[0]
                )->{$callback[1]}($arrTh, $arrTd, $arrExcelPattern, $objMap, $this);
            }
        }

        // Build the country array, to give the correct syntax to users
        $arrCountries = [];

        foreach ($this->countries->getCountries() as $strIsoCode => $strName) {
            $arrCountries[$strIsoCode]['current'] = $strName;
        }

        foreach ($this->countries->getCountries('en') as $strIsoCode => $strName) {
            $arrCountries[$strIsoCode]['en'] = $strName;
        }

        $strCountries = '';

        foreach ($arrCountries as $strIsoCode => $arrNames) {
            $strCountries .= '<tr>';
            $strCountries .= '<td>' . $strIsoCode . '</td>';
            $strCountries .= '<td>' . $arrNames['current'] . '</td>';
            $strCountries .= '<td>' . $arrNames['en'] . '</td>';
            $strCountries .= '</tr>';
        }

        $arrLanguages = $this->locales->getLanguages();

        $objTemplate = new BackendTemplate('be_wem_geodata_import_form');

        $objTemplate->backButtonHref = StringUtil::ampersand(
            str_replace('&key=import', '', Environment::get('request'))
        );
        $objTemplate->backButtonTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];

        $objTemplate->downloadSampleButtonHref = StringUtil::ampersand(
            str_replace('&key=import', '&key=download_import_sample', Environment::get('request'))
        );
        $objTemplate->downloadSampleButtonTitle = StringUtil::specialchars(
            $GLOBALS['TL_LANG']['tl_wem_map_item']['downloadSampleBTTitle']
        );
        $objTemplate->downloadSampleButtonLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['downloadSampleBT'];

        $objTemplate->formId = $formId;
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
        $objTemplate->formRequestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     */
    public function import(Request $request, Map $objMap): never
    {
        $updateExistingItems = (bool) Input::post('update_existing_items');
        $deleteExistingItems = (bool) Input::post('delete_existing_items_not_in_import_file');

        /** @var FileUpload $objUploader */
        $objUploader = new FileUpload();

        $arrUploaded = $objUploader->uploadTo('system/tmp');
        if (empty($arrUploaded)) {
            Message::addError($GLOBALS['TL_LANG']['ERR']['all_fields']);
            Controller::reload();
        }

        $arrExcelPattern = $objMap->getImportPattern();

        // HOOK: add custom logic
        if (

            isset($GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS']) && \is_array(
                $GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS']
            )

        ) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATAIMPORTLOCATIONS'] as $callback) {
                static::importStatic(
                    $callback[0]
                )->{$callback[1]}($arrUploaded, $arrExcelPattern, $updateExistingItems, $deleteExistingItems, $objMap, $this);

                System::setCookie('BE_PAGE_OFFSET', 0, 0);
                $this->reload();
            }
        }

        foreach ($arrUploaded as $strFile) {
            $objFile = new File($strFile, true);
            $spreadsheet = IOFactory::load(
                System::getContainer()->getParameter('kernel.project_dir') . '/' . $objFile->path
            );

            $sheetData = $spreadsheet->getActiveSheet()
                ->toArray(null, true, true, true)
            ;
            $arrLocations = [];
            $nbRow = 0;

            foreach ($sheetData as $arrRow) {
                if (array_filter($arrRow) === []) {
                    continue;
                }

                ++$nbRow;

                try {
                    $arrLocation = [];
                    $arrLocation['country'] = '';
                    $arrLocation['city'] = '';

                    foreach ($arrRow as $strColumn => $strValue) {
                        // strColumn = Excel Column strValue = Value in the current arrRow, at the

                        // column strColumn
                        $strValue = \is_string($strValue) ? trim($strValue) : $strValue;

                        switch ($arrExcelPattern[$strColumn]) {
                            case 'category':
                                $objCategory = Category::findOneByTitle($strValue);

                                if (! $objCategory) {
                                    break;
                                }

                                $arrLocation['category'] = $objCategory->id;
                                break;
                            case 'region':
                                if ($strValue !== null) {
                                    $arrLocation['admin_lvl_1'] = $strValue;
                                }

                                break;
                            case 'country':
                                if (empty($strValue)) {
                                    throw new Exception(\sprintf(
                                        'Empty value for columns %s (%s)',
                                        $strColumn,
                                        $arrExcelPattern[$strColumn]
                                    ));
                                }

                                if (\strlen($strValue) === 2) {
                                    $arrLocation['country'] = $strValue;
                                } else {
                                    $arrLocation['country'] = CountriesUtil::getCountryISOCodeFromFullname($strValue);
                                }

                                break;
                            case 'picture':
                                if(!empty($strValue)){
                                    $arrLocation['picture'] = hex2bin($strValue);
                                }
                            break;
                            default:
                                if ($strValue === null) {
                                    break;
                                }

                                $arrLocation[$arrExcelPattern[$strColumn]] = $strValue;
                        }
                    }

                    $arrLocation['continent'] = CountriesUtil::getCountryContinent($arrLocation['country']);
                    $arrLocations[$nbRow] = $arrLocation;
                } catch (Exception $e) {
                    Message::addError(
                        \sprintf(
                            $GLOBALS['TL_LANG']['tl_wem_map_item']['errorOnItemImport'],
                            $nbRow,
                            $e->getMessage()
                        )
                    );
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
                    $arrLocation['alias'] = $arrLocation['alias'] ?? StringUtil::generateAlias(
                        $arrLocation['title'] . '-' . $arrLocation['city'] . '-' . $arrLocation['country'] . '-' . ($k + 1)
                    );

                    if ($updateExistingItems) {
                        $objLocation = MapItem::findItems(['alias' => $arrLocation['alias'],
                            'pid' => $objMap->id], 1);

                        // Create if don't exists
                        if (! $objLocation instanceof Collection) {
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
                        $objLocation->{$strColumn} = $varValue;
                    }

                    $objLocation->save();
                    $arrNewLocations[] = $objLocation->id;
                } catch (Exception $e) {
                    ++$intErrors;
                    if ($blnCreated) {
                        --$intCreated;
                    } elseif ($blnUpdated) {
                        --$intUpdated;
                    }

                    Message::addError(
                        \sprintf(
                            $GLOBALS['TL_LANG']['tl_wem_map_item']['errorOnItemImport'],
                            $objLocation->title,
                            $e->getMessage()
                        )
                    );
                }
            }

            if ($deleteExistingItems) {
                $objLocations = MapItem::findItems(['pid' => $objMap->id,
                    'published' => 1]);

                if ($objLocations instanceof Collection) {
                    while ($objLocations->next()) {
                        if (! \in_array($objLocations->id, $arrNewLocations, true)) {
                            $objLocations->delete();
                            ++$intDeleted;
                        }
                    }
                }
            }
        }

        Message::addConfirmation(
            \sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['createdConfirmation'], $intCreated)
        );

        Message::addInfo(
            \sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['updatedConfirmation'], $intUpdated)
        );

        Message::addInfo(
            \sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['deletedConfirmation'], $intDeleted)
        );

        Message::addError(\sprintf($GLOBALS['TL_LANG']['tl_wem_map_item']['errorsConfirmation'], $intErrors));

        Controller::reload();
    }
}