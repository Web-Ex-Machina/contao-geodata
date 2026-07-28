<?php

declare(strict_types=1);

/*
 * Geodata Bundle for Contao Open Source CMS
 * @author     Web Ex Machina
 *
 * @see        https://github.com/Web-Ex-Machina/contao-geodata
 * @license    https://www.apache.org/licenses/LICENSE-2.0
 */

namespace WEM\GeoDataBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\Database;
use Contao\Model;

class CoreContainer extends Backend
{
    /**
     * Import the back end user object.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Sync basic data between pivot tables.
     *
     * @param [array]  $varValues       [Usually an array of IDs]
     * @param [string] $strTable        [Table where to sync]
     * @param [int]    $intParentId     [Parent ID]
     * @param [string] $strParentField  [Parent Field]
     * @param [string] $strForeignField [Foreign field where to sync values]
     */
    public function syncData(
        $varValues,
        $strTable,
        $intParentId,
        $strParentField,
        $strForeignField
    ): void {
        // Found Model class
        $stdModel = Model::getClassFromTable($strTable);

        // step 1 - update existing recipients, add new ones
        foreach ($varValues as $id) {
            $objModel = $stdModel::findItems([
                $strParentField => $intParentId,
                $strForeignField => $id,
            ], 1);

            if (! $objModel) {
                $objModel = new $stdModel();
                $objModel->createdAt = time();
                $objModel->{$strParentField} = $intParentId;
                $objModel->{$strForeignField} = $id;
            }

            $objModel->tstamp = time();
            $objModel->save();
        }

        // step 2 - remove all ids not in $varValues
        if ($varValues) {
            Database::getInstance()->prepare(
                \sprintf(
                    'DELETE FROM %s WHERE %s = %s AND %s.%s NOT IN (%s)',
                    $strTable,
                    $strParentField,
                    $intParentId,
                    $strTable,
                    $strForeignField,
                    implode(',', array_map('intval', $varValues))
                )
            )->execute();
        }
    }
}
