<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    FiltersController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_filters'
)]
class FiltersController extends ModuleController
{
    public const TYPE = 'wem_geodata_filters';

    public function __construct(
        private readonly ContentUrlGenerator $contentUrlGenerator
    ) {
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
