<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

use Contao\Input;

if (

    Input::get(
        'do'
    ) === 'wem-maps'

) {
    $GLOBALS['TL_DCA']['tl_content']['config']['ptable'] = 'tl_wem_map_item';
}
