# Hooks for package `contao-geodata`

This file list all available hooks in this package.

## List

| name | description |
--- | ---
| `WEMGEODATAIMPORTLOCATIONS` | Called when a set of locations are imported. Completely overrides default behaviour.
| `WEMGEODATADOWNLOADLOCATIONSSAMPLE` | Called when generating a sample file for later locations import. Either alter the given `\PhpOffice\PhpSpreadsheet\Spreadsheet` object or completely overrides default behaviour.
| `WEMGEODATADISPLAYLOCATIONSSAMPLE` | Called when generating a sample file format to display. Returns an array with header columns & exmaple rows.
| `WEMGEODATADOWNLOADLOCATIONSEXPORT` | Called when generating an export file. Either alter the given `\PhpOffice\PhpSpreadsheet\Spreadsheet` object or completely overrides default behaviour.

## Details

### WEMGEODATAIMPORTLOCATIONS

This hook is called when a set of locations are imported. 
Warning : **it completely overrides default behaviour**.

**Return value** : `void`

**Arguments**:
Name | Type | Description
--- | --- | ---
$arrUploaded | `array` | Array of uploaded files path
$arrExcelPattern | `array` | Array to make column in file match a field in the `\WEM\GeoDataBundle\Model\Item` object
$objMap | `\WEM\GeoDataBundle\Model\Map` | The `\WEM\GeoDataBundle\Model\Map` in which items are to be imported
$caller | `\WEM\GeoDataBundle\Backend\Callback` | The calling object

**Code**:
```php
public function importLocations(
	array $arrUploaded, 
	array $arrExcelPattern, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): void
{
	// import locations
}
```

### WEMGEODATADOWNLOADLOCATIONSSAMPLE

This hook is called when generating a sample file for later locations import. Either alter the given `\PhpOffice\PhpSpreadsheet\Spreadsheet` object or completely overrides default behaviour. 
Warning : **To completely overrides default behaviour, your hook must end with an `exit` call**.

**Return value** : `null|\PhpOffice\PhpSpreadsheet\Spreadsheet`

**Arguments**:
Name | Type | Description
--- | --- | ---
$objSpreadsheet | `\PhpOffice\PhpSpreadsheet\Spreadsheet` | The generated `\PhpOffice\PhpSpreadsheet\Spreadsheet`
$arrExcelPattern | `array` | Array to make column in file match a field in the `\WEM\GeoDataBundle\Model\Item` object
$objMap | `\WEM\GeoDataBundle\Model\Map` | The `\WEM\GeoDataBundle\Model\Map` in which items are to be imported
$caller | `\WEM\GeoDataBundle\Backend\Callback` | The calling object

**Code**:
```php
// ex 1 : only alter
public function downloadImportSample(
	\PhpOffice\PhpSpreadsheet\Spreadsheet $objSpreadsheet, 
	array $arrExcelPattern, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
	$objSheet = $objSpreadsheet->getActiveSheet();
	$objSheet->setCellValue('A1', 'A value');
	return $objSpreadsheet;
}

// ex 2 : completely override
public function downloadImportSample(
	\PhpOffice\PhpSpreadsheet\Spreadsheet $objSpreadsheet, 
	array $arrExcelPattern, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): void
{
	$rows = [];
	foreach ($arrExcelPattern as $strExcelColumn => $strDbColumn) {
		$rows[0][$strExcelColumn] = $strDbColumn;
		$rows[1][$strExcelColumn] = $strDbColumn;
	}
	$json = json_encode(['rows'=>$rows]);
	header('Content-Disposition: attachment;filename="my_superb_json.json"');
    header('Content-Type: application/json');
    header('Cache-Control: max-age=0');
    echo $json;
    exit;
}
```

### WEMGEODATADISPLAYLOCATIONSSAMPLE

This hook is called when generating a sample file format to display. Returns an array with header columns & exmaple rows.

Warning : **`headers` and each row in `rows` must have the same length**.

**Return value** : `array`

**Arguments**:
Name | Type | Description
--- | --- | ---
$headers | `array` | Array of header cells
$rows | `array` | Array of rows
$arrExcelPattern | `array` | Array to make column in file match a field in the `\WEM\GeoDataBundle\Model\Item` object
$objMap | `\WEM\GeoDataBundle\Model\Map` | The `\WEM\GeoDataBundle\Model\Map` in which items are to be imported
$caller | `\WEM\GeoDataBundle\Backend\Callback` | The calling object

**Code**:
```php
public function displayLocationsSample(
	array $headers, 
	array $rows, 
	array $arrExcelPattern, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): array
{
	$headers[] = '<th>My new column</th>';
	foreach($rows as $index => $row){
		$rows[$index][] = '<td>My new column example value</td>';
	}
	return [$headers, $rows];
}
```

### WEMGEODATADOWNLOADLOCATIONSEXPORT

This hook is called when generating an export file. Either alter the given `\PhpOffice\PhpSpreadsheet\Spreadsheet` object or completely overrides default behaviour. 
Warning : **To completely overrides default behaviour, your hook must end with an `exit` call**.

**Return value** : `null|\PhpOffice\PhpSpreadsheet\Spreadsheet`

**Arguments**:
Name | Type | Description
--- | --- | ---
$objSpreadsheet | `\PhpOffice\PhpSpreadsheet\Spreadsheet` | The generated `\PhpOffice\PhpSpreadsheet\Spreadsheet`
$arrExcelPattern | `array` | Array to make column in file match a field in the `\WEM\GeoDataBundle\Model\Item` object
$objLocations | `\Contao\Model\Collection` | A collection of `\WEM\GeoDataBundle\Model\MapItem`
$arrCountries | `array` | Countries list
$objMap | `\WEM\GeoDataBundle\Model\Map` | The `\WEM\GeoDataBundle\Model\Map` in which items are to be exported
$exportFormat | `string` | The desired export format
$caller | `\WEM\GeoDataBundle\Backend\Callback` | The calling object

**Code**:
```php
// ex 1 : only alter
public function exportLocations(
	\PhpOffice\PhpSpreadsheet\Spreadsheet $objSpreadsheet, 
	array $arrExcelPattern, 
	\Contao\Model\Collection $objLocations, 
	array $arrCountries, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	string $exportFormat, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): \PhpOffice\PhpSpreadsheet\Spreadsheet
{
	$objSheet = $objSpreadsheet->getActiveSheet();
	$currentRow = 0;
	while($objLocations->next()){
		$currentRow++;
		$objSheet->setCellValue("A".$currentRow, 'My title : '.$objLocations->current()->title);
	}

	return $objSpreadsheet;
}

// ex 2 : completely override
public function exportLocations(
	\PhpOffice\PhpSpreadsheet\Spreadsheet $objSpreadsheet, 
	array $arrExcelPattern, 
	\Contao\Model\Collection $objLocations, 
	array $arrCountries, 
	\WEM\GeoDataBundle\Model\Map $objMap, 
	string $exportFormat, 
	\WEM\GeoDataBundle\Backend\Callback $caller
): void
{
	$rows = [];

	$currentRow = 0;
	while($objLocations->next()){
		$currentRow++;

		foreach($arrExcelPattern as $strDbColumn => $strExcelColumn){
			switch ($strDbColumn) {
                case 'country':
                    $rows[$currentRow][$strExcelColumn] = $arrCountries[$objLocations->$strDbColumn];
                break;
                default:
                    $rows[$currentRow][$strExcelColumn] =  $objLocations->current()->$strDbColumn;
            }
			
		}
	}
	$json = json_encode(['rows'=>$rows]);
	header('Content-Disposition: attachment;filename="my_superb_json.json"');
    header('Content-Type: application/json');
    header('Cache-Control: max-age=0');
    echo $json;
    exit;
}
```