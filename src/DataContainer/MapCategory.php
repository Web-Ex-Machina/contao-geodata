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

class MapCategory extends CoreContainer
{
    /**
     * Design each row of the DCA.
     *
     * @return string
     */
    public function listItems($row)
    {
        return $row['title'];
    }
}
