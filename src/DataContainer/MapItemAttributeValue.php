<?php

declare(strict_types=1);

/**
 * Geodata for Contao Open Source CMS
 * Copyright (c) 2015-2022 Web ex Machina
 *
 * @category ContaoBundle
 * @package  Web-Ex-Machina/contao-geodata
 * @author   Web ex Machina <contact@webexmachina.fr>
 * @link     https://github.com/Web-Ex-Machina/contao-geodata/
 */

namespace WEM\GeoDataBundle\DataContainer;

use Contao\Backend;

class MapItemAttributeValue extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
        $this->import('BackendUser', 'User');
    }

    /**
     * Design each row of the DCA.
     *
     * @param array $arrRow
     *
     * @return string
     */
    public function listItems($arrRow)
    {
        return $arrRow['attribute'].' '.$arrRow['value'];
    }
}
