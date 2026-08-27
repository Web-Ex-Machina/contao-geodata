<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    MapController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_map'
)]
class MapController extends ModuleController
{
    public const TYPE = 'wem_geodata_map';

    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        return $template->getResponse();
    }
}
