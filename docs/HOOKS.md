# Hooks for package `contao-geodata`

This file list all available hooks in this package.

## List

| name | description |
--- | ---
| `WEMGEODATAIMPORTLOCATIONS` | Called when a set of locations are imported. Completely overrides default behaviour.

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