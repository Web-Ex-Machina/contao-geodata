<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Api;

use Contao\CoreBundle\Framework\ContaoFramework;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use WEM\UtilsBundle\Classes\CountriesUtil;

#[Route(
    '/api/geodata',
    name: 'wem_api_portfolio',
    defaults: ['_scope' => 'frontend', '_token_check' => false]
)]
#[AsController]
class ApiController
{
    public function __construct(
        private ContaoFramework $framework,
    ) {
        $this->framework->initialize();

        // parent::__construct();
    }

    #[Route("/")]
    public function view(Request $request): Response
    {
        return new Response('Hello World!');
    }
}
