<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Api;

use Contao\Environment;
use Contao\FilesModel;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\Model\Collection;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use WEM\GeoDataBundle\Model\Category;
use WEM\GeoDataBundle\Model\Map;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\CountriesUtil;
use WEM\UtilsBundle\Classes\StringUtil;

#[Route(
    '/api/geodata',
    name: 'wem_api_geodata',
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

    #[Route(
        '/doc',
        name: 'doc',
        methods: ['GET']
    )]
    public function doc(Request $request): JsonResponse
    {
        $routes = [];

        $routes[] = [
            'usage' => 'To retrieve a list of map items based on various settings',
            'path' => '/api/geodata/get/items',
            'arguments' => [
                'params' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'limit' => [
                    'type' => 'int',
                    'mandatory' => false,
                ],
                'offset' => [
                    'type' => 'int',
                    'mandatory' => false,
                ],
                'options' => [
                    'type' => 'string',
                    'mandatory' => false,
                ],
            ],
        ];

        $routes[] = [
            'usage' => 'To count map items based on various settings',
            'path' => '/api/geodata/count/items',
            'arguments' => [
                'params' => [
                    'type' => 'array',
                    'mandatory' => false,
                ],
                'options' => [
                    'type' => 'string',
                    'mandatory' => false,
                ],
            ],
        ];

        return new JsonResponse(['routes' => $routes]);
    }

    #[Route("/get/items")]
    public function getItems(Request $request): Response
    {
        $locale = $request->query->has('locale') ? $request->query->get('locale') : $GLOBALS['TL_LANGUAGE'];
        $params = $request->query->has('params') ? (array) $request->query->all('params') : [];
        $limit = $request->query->has('limit') ? (int) $request->query->get('limit') : 30;
        $offset = $request->query->has('offset') ? (int) $request->query->get('offset') : 0;
        $options = $request->query->has('options') ? (array) $request->query->all('options') : [];

        // Default published settings to 1
        if (!array_key_exists('published', $params)) {
            $params['published'] = 1;
        }

        // Allow people to choose order direction
        if (array_key_exists('order', $options) && false !== strpos($options['order'], "-")) {
            $chunks = explode("-", $options['order']);
            $options['order'] = urldecode($chunks[0]) . ' ' . strtoupper($chunks[1]);
        } else if (array_key_exists('order', $options)) {
            $options['order'] = urldecode($options['order']) . ' DESC';
        }

        $objItems = MapItem::findItems($params, $limit, $offset, $options);

        if ($objItems instanceof Collection) {
            while ($objItems->next()) {
                $items[$objItems->id] = $this->prepareItem($objItems->current(), $locale);
            }

            return new JsonResponse($items, Response::HTTP_OK);
        }

        return new JsonResponse(null, Response::HTTP_NOT_FOUND);
    }

    #[Route("/count/items")]
    public function countItems(Request $request): Response
    {
        $locale = $request->query->has('locale') ? $request->query->get('locale') : $GLOBALS['TL_LANGUAGE'];
        $params = $request->query->has('params') ? (array) $request->query->all('params') : [];
        $options = $request->query->has('options') ? (array) $request->query->all('options') : [];

        // Default published settings to 1
        if (!array_key_exists('published', $params)) {
            $params['published'] = 1;
        }

        return new JsonResponse(MapItem::countItems($params, $options), Response::HTTP_OK);
    }

    protected function prepareItem(MapItem $item, string $locale = null): ?array
    {
        // Return null if the item is not published
        if ('' === $item->published) {
            return null;
        }

        $base = Environment::get('base');
        $data = $item->row();

        foreach ($data as $c => &$v) {
            switch ($c) {
                case 'categories':
                    $categories = StringUtil::deserialize($v);
                    $v = [];
                    if ($categories && is_array($categories) && !empty($categories)) {
                        foreach ($categories as $category) {
                            $objCategory = Category::findById($category);
                            $v[] = $objCategory->row();
                        }
                    }
                break;

                case 'picture':
                    if (null !== $v) {
                        $objFile = FilesModel::findByUuid($v);
                        $v = $base . $objFile->path;
                    }
                break;
                default:
                    // do nuthin
            }
        }

        return $data;
    }
}
