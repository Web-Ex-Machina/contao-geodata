<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\Controller\FrontendModule\AbstractFrontendModuleController;
use Contao\ModuleModel;
use Contao\System;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Common functions for job portfolios modules.
 *
 * @author Web ex Machina <https://www.webexmachina.fr>
 */
abstract class ModuleController extends AbstractFrontendModuleController
{
    protected ModuleModel $model;
    protected RequestStack $request;

    public function __construct() {
        $this->request = System::getContainer()->get('request_stack');
    }
}
