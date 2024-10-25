Extension "Geodata" for Contao Open Source CMS
========

1.1.0 - 2024-10-25
---
- UPDATED : Clean code for PHP 8 compatibility
- FIXED : CSS issue

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