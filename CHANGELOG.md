Extension "Geodata" for Contao Open Source CMS
========

2.0.3 - 2024-11-05
---
- UPDATED : if some map items' marker config' values are missing, use the one from the map if they exists

2.0.2 - 2024-11-04
---
- FIXED : `generateBreadcrumb` hook trying to do stuff when it shouldn't

2.0.1 - 2024-11-04
---
- FIXED : `DisplayMap` module trying to display filters if no filters to display for the current map
- FIXED : `DisplayMap` module's `$filters` are now set as an empty array by default
- ADDED : migration guide to help upgrading from v1.x to v2.x

2.0.0 - 2024-11-04
---
- UPDATED : Encrypt Google Maps key in database using `webexmachina/contao-utils`
- UPDATED : Clean code for PHP 8 compatibility
- UPDATED : Bump dependencies
- FIXED : CSS issue
- UPDATED : Maps can be loaded asynchronously if there are more than a certain amount of locations to display (`tl_module.wem_geodata_map_nbItemsToForceAjaxLoading`). Setting this to "0" disables this behaviour. "0" is the default value.
- UPDATED : new template `mod_wem_geodata_list_inmap_item` to display items in "in map" list
- UPDATED : new JS variables `rt`,`mapModuleId`,`blnLoadInAjax` in template `mod_wem_geodata_map` :warning: They are mandatory

1.0.8 - 2024-08-08
---
- FIXED : various bugs in `1.0.7`

1.0.7 - 2024-08-08
---
- UPDATED : now requires `webexmachina/contao-utils` versions `^1.0||^2.0`

1.0.6 - 2024-02-02
---
- FIXED : Location List now correctly uses the configured number of items

1.0.5 - 2024-02-01
---
- FIXED : Location Reader using the right class to set map item's description as page's description

1.0.4 - 2023-10-31
---
- UPDATED : JSON encoding of locations when transmitting them to templates' JS

1.0.3 - 2023-08-22
---
- UPDATED : assets combiner now use this package's version as version

1.0.2 - 2023-08-16
---
- UPDATED : bundle now requires [webexmachina/contao-utils](https://github.com/Web-Ex-Machina/contao-utils) ^1.0

1.0.1 - 2023-08-08
---
- ADDED : better interaction with `marcel-mathias-nolte/contao-filesmanager-fileusage`'s bundle

1.0.0 - 2023-08-07
---
First release

1.0.0-rc6 - 2023-07-17
---
- UPDATED : map items can now have multiple categories
- UPDATED : filters in locations' list

1.0.0-rc5 - 2023-02-06
---
- UPDATED : moving hook WEMGEODATABUILDFILTERSSINGLEFILTEROPTION calls to allow more flexibility

1.0.0-rc4 - 2023-02-02
---
- UPDATED : translations for administrative levels
- ADDED : Hook `WEMGEODATADISPLAYLOCATIONSSAMPLE`
- ADDED : Hook `WEMGEODATADOWNLOADLOCATIONSEXPORT`
- ADDED : Hook `WEMGEODATAGETLOCATION`
- ADDED : Hook `WEMGEODATABUILDFILTERSSINGLEFILTEROPTION`
- ADDED : Hook `WEMGEODATAMAPITEMFORMATSTATEMENT`