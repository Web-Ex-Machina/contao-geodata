# Migration guide

## from v1.x to v2.x

### Map configuration changes

#### Geocoding with `Google Maps`

:warning: Geocoding with `Google Maps` is deprecated and doesn't work anymore.

#### Geocoding with `Nominatim`

If you are using `Nominatim` as your geocoding provider, you have to fill the `geocodingProviderNominatimReferer` in order to identify your application (the value can be freely set).
See : [Nominatim Usage Policy](https://operations.osmfoundation.org/policies/nominatim/)

### New templates

:warning: This part is only relevant to you if you have overridden `mod_wem_geodata_map` or `mod_wem_geodata_list_inmap` templates.

#### Inmap list items

When a location's list is showed within a map, the items are not managed directly in the `mod_wem_geodata_list_inmap` template anymore.
They are now placed in `mod_wem_geodata_list_inmap_item`.

#### Filters

The filters are no longer managed directly in the `mod_wem_geodata_map` template.
There are now a template for each filter position :

- No filters : no template
- Above : `mod_wem_geodata_map_filters_above`
- Left panel : `mod_wem_geodata_map_filters_leftpanel`
- Below : `mod_wem_geodata_map_filters_below`

### Changes in the `mod_wem_geodata_map` template

#### Displaying filters

As stated above, filters are no longer managed directly in the template

```html
<?php if($this->filters && "above" == $this->filters_position): ?>  <!-- values can be "above","leftpanel" or "below" -->
	<div class="map__filters__container"><?= $this->filters_html; ?></div>
<?php endif; ?>
```

#### AJAX loading message

In order to make the AJAX loading map behavior work, this snippet must be present in the template (right after the `map__wrapper` by default)

```html
<div class="map__loading__overlay hidden">
	<div class="map__loading__overlay__text">
		<?= $this->trans('WEM.LOCATIONS.LABEL.mapLoading'); ?>
	</div>
</div>
```

#### New & updated javascript variables

```html
<script type="text/javascript">
	// unchanged
	objMapConfig      = <?= json_encode($this->config) ?>;
    categories        = <?= $this->categories ? json_encode($this->categories) : '{}' ?>;
	
	// modified
	objMapData        = <?= $this->blnLoadInAjax ? '[]' : json_encode($this->locations, JSON_INVALID_UTF8_IGNORE | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
	objMapFilters     = <?= !$this->blnLoadInAjax && $this->filters ? json_encode($this->filters ?? []) : '{}' ?>;
	
	// new
	rt     = '<?= $this->rt ?>';
	mapModuleId     = '<?= $this->moduleId ?>';
	blnLoadInAjax   = <?= $this->blnLoadInAjax ? 1 : 0; ?>;
</script>
```