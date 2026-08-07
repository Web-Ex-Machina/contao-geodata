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
use Contao\Input;
use Contao\StringUtil;
use WEM\GeoDataBundle\Model\Map;

/**
 * Provide backend functions to Locations Extension.
 */
class DownloadSampleController extends AbstractController
{
    public function __construct(
    ) {
    }

    public function run(DataContainer $objDc): string|null 
    {
        if (!Input::get('id')) {
            return '';
        }

        $objMap = Map::findById(Input::get('id'));

        if (!$objMap) {
            return '';
        }

        // Generate the spreadsheet
        $objSpreadsheet = new Spreadsheet();
        $objSheet = $objSpreadsheet->getActiveSheet();

        $arrExcelPattern = [];

        // Preformat Excel Pattern (key = Excel column, value = DB Column)
        foreach (StringUtil::deserialize($objMap->excelPattern) as $c) {
            $arrExcelPattern[$c['value']] = $c['key'];
        }

        foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
            $strDbColumn = $strDbColumn === 'region' ? 'admin_lvl_1' : $strDbColumn;
            $objSheet->setCellValue($strExcelColumn . '1', $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0]);
            $objSheet->setCellValue($strExcelColumn . '2', $GLOBALS['TL_LANG']['tl_wem_map_item'][$strDbColumn][0]);
        }

        // And send to browser
        $strFilename = date('Y-m-d_H-i') . '_import-locations-sample';
        $format = IOFactory::WRITER_XLSX;

        // HOOK: add custom logic
        if (
            isset($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE']) && \is_array(
                $GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE']
            )
        ) {
            foreach ($GLOBALS['TL_HOOKS']['WEMGEODATADOWNLOADLOCATIONSSAMPLE'] as $callback) {
                $objSpreadsheetTemp = static::importStatic(
                    $callback[0]
                )->{$callback[1]}($objSpreadsheet, $arrExcelPattern, $objMap, $this);
                
                if ($objSpreadsheetTemp) {
                    $objSpreadsheet = $objSpreadsheetTemp;
                }
            }
        }

        header('Content-Disposition: attachment;filename="' . $strFilename . '.xlsx"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Cache-Control: max-age=0');

        $writer = IOFactory::createWriter($objSpreadsheet, $format);
        $writer->save('php://output');
        exit;
    }
}