<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    ReaderController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_reader'
)]
class ReaderController extends ModuleController
{
    public const TYPE = 'wem_geodata_reader';

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
