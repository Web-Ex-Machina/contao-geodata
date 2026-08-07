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
use Contao\BackendTemplate;
use Contao\Config;
use Contao\Controller;
use Contao\Environment;
use Contao\Input;
use Contao\Message;
use Contao\Model\Collection;
use Contao\StringUtil;
use Contao\System;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use WEM\GeoDataBundle\Classes\Util;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Provide backend functions to Locations Extension.
 */
class ExportController extends AbstractController
{
    public function __construct(
        private readonly RequestStack $requestStack,
    ) {
    }

    public function run(): string
    {
        $formId = 'tl_geodata_export';
        $request = $this->requestStack->getCurrentRequest();

        if ($request->request->get('FORM_SUBMIT') === $formId) {
            $this->export($request);
        }

        return $this->getForm($formId);
    }

    protected function getForm(string $formId): string
    {
        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findById(Input::get('id'));

        if (!$objMap) {
            return '';
        }

        $arrCategories = [];
        $categories = Category::findItems(['pid' => $objMap->id]);
        if ($categories instanceof Collection) {
            while ($categories->next()) {
                $arrCategories[$categories->id] = $categories->title;
            }
        }

        $arrCountriesSystem = Util::getCountries();
        $arrCountries = [];
        $items = MapItem::findItems(['pid' => $objMap->id]);
        if ($items instanceof Collection) {
            while ($items->next()) {
                $arrCountries[$items->country] = $arrCountriesSystem[strtoupper($items->country)] 
                    ?? $arrCountriesSystem[strtolower($items->country)];
            }
        }

        $objTemplate = new BackendTemplate('be_wem_geodata_export_form');

        $objTemplate->backButtonHref = StringUtil::ampersand(
            str_replace('&key=export_form', '', Environment::get('request'))
        );
        $objTemplate->backButtonTitle = StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['backBTTitle']);
        $objTemplate->backButtonLabel = $GLOBALS['TL_LANG']['MSC']['backBT'];
        $objTemplate->formAction = StringUtil::ampersand(
            str_replace('key=export_form', 'key=export', Environment::get('request')),
            true
        );

        $objTemplate->widgetSettingsTitle = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsTitle'];
        $objTemplate->widgetSettingsFormatLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsFormatLabel'];
        $objTemplate->widgetSettingsLimitToCategoriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCategoriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCategoriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCategoriesSelectLabel'];
        $objTemplate->widgetSettingsLimitToCountriesCheckboxLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCountriesCheckboxLabel'];
        $objTemplate->widgetSettingsLimitToCountriesSelectLabel = $GLOBALS['TL_LANG']['tl_wem_map_item']['exportSettingsLimitToCountriesSelectLabel'];
        $objTemplate->formSubmitValue = StringUtil::specialchars(
            $GLOBALS['TL_LANG']['tl_wem_map_item']['export_form'][0]
        );

        $objTemplate->formId = $formId;
        $objTemplate->categories = $arrCategories;
        $objTemplate->countries = $arrCountries;
        $objTemplate->formRequestToken = System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue();
        $objTemplate->formMaxFileSize = Config::get('maxFileSize');

        return $objTemplate->parse();
    }

    /**
     * Export the Locations of the current map, according to the pattern set.
     */
    public function export(Request $request): never
    {
        $message = $this->getContaoAdapter(Message::class);
        $system = $this->getContaoAdapter(System::class);
        $controller = $this->getContaoAdapter(Controller::class);
        $referer = $system->getReferer();

        if (!Input::get('id')) {
            $message->addError("No ID found");
            
            $controller->reload();
        }

        $objMap = Map::findById(Input::get('id'));

        if (!$objMap) {
            $message->addError("No map found");
            
            $controller->reload();
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
        foreach (StringUtil::deserialize($objMap->excelPattern) as $c) {
            $arrExcelPattern[$c['key']] = $c['value'];
        }

        // Fetch all the locations
        $arrCountries = Util::getCountries();
        $objLocations = MapItem::findItems($params);

        // Break if no locations
        if (!$objLocations instanceof Collection) {
            $message->addError($GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['noLocationsFound']);
            
            $controller->reload();
        }

        // Format for the Excel
        $arrRows = [];

        while ($objLocations->next()) {
            $arrRow = null;

            foreach ($arrExcelPattern as $strDbColumn => $strExcelColumn) {
                switch ($strDbColumn) {
                    case 'country':
                        $arrRow[$strExcelColumn] = $arrCountries[$objLocations->{$strDbColumn}];
                        break;
                    case 'region':
                        $arrRow[$strExcelColumn] = $objLocations->admin_lvl_1;
                        break;
                    case 'picture':
                        $arrRow[$strExcelColumn] = null !== $objLocations->picture ? bin2hex($objLocations->picture) : '';
                        break;
                    default:
                        $arrRow[$strExcelColumn] = $objLocations->{$strDbColumn};
                }
            }

            if ($arrRow !== null) {
                $arrRows[] = $arrRow;
            }
        }

        // Generate the spreadsheet
        $objSpreadsheet = new Spreadsheet();
        $objSheet = $objSpreadsheet->getActiveSheet();

        // Fill the cells of the Excel
        foreach ($arrRows as $intRow => $arrRow) {
            foreach ($arrRow as $strColumn => $strValue) {
                $objSheet->setCellValue($strColumn . ($intRow + 1), $strValue);
            }
        }

        // And send to browser
        $filename = date('Y-m-d_H-i') . '_export-locations';

        switch (strtolower(Input::post('format') ?? '')) {
            case 'csv':
                $format = IOFactory::WRITER_CSV;
                $filename .= '.csv';
                break;
            case 'xlsx':
                $format = IOFactory::WRITER_XLSX;
                $filename .= '.xlsx';
                break;
            default:
                throw new Exception(\sprintf(
                    $GLOBALS['TL_LANG']['WEM']['LOCATIONS']['ERROR']['unknownExportFormat'],
                    implode('","', ['csv', 'xlsx'])
                ));
        }

        // HOOK: add custom logic
        if (
            isset($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT']) && \is_array(
                $GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT']
            )
        ) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSEXPORT'] as $callback) {
                $objSpreadsheetTemp = static::importStatic(
                    $callback[0]
                )->{$callback[1]}($objSpreadsheet, $arrExcelPattern, $objLocations->reset(), $arrCountries, $objMap, $format, $this);
                
                if ($objSpreadsheetTemp) {
                    $objSpreadsheet = $objSpreadsheetTemp;
                }
            }
        }

        $writer = IOFactory::createWriter($objSpreadsheet, $format);

        if ($format === IOFactory::WRITER_CSV) {
            $writer->setDelimiter(';');
            $writer->setEnclosure('"');
            $writer->setLineEnding("\r\n");
            $writer->setSheetIndex(0);
        }

        $root = System::getContainer()->getParameter('kernel.project_dir');
        $filepath = $root . '/system/tmp/' . $filename;
        $writer->save($filepath);

        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);

        throw new ResponseException($response);
    }
}