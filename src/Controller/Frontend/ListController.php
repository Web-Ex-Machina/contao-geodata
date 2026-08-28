<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\Config;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Exception\PageNotFoundException;
use Contao\Environment;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\Pagination;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

#[AsFrontendModule(
    ListController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_list'
)]
class ListController extends ModuleController
{
    public const TYPE = 'wem_geodata_list';

    protected ?array $config = [];

    protected ?int $page = 0;

    protected ?int $limit = 0;

    protected ?int $offset = 0;

    protected array $options = [];

    public function __construct() 
    {
        parent::__construct();
    }

    /**
     * Generate the module.
     */
    protected function getResponse(Template $template, ModuleModel $model, Request $request): Response
    {
        $this->model = $model;
        $this->limit = null;
        $this->offset = 0;
        $this->loadMap();

        // Maximum number of items
        if ($this->model->numberOfItems > 0) {
            $this->limit = $this->model->numberOfItems;
        }

        $template->items = [];

        // Prepare config
        $this->config = [
            'pid' => $this->map->id,
            'published' => 1,
        ];

        // Retrieve filters
        if ([] !== $_GET || [] !== $_POST) {
            foreach (array_keys($_GET) as $f) {
                if (!str_contains($f, 'geodata_filter_')) {
                    continue;
                }

                if (Input::get($f)) {
                    $this->config[str_replace('geodata_filter_', '', $f)] = Input::get($f);
                }
            }

            foreach (array_keys($_POST) as $f) {
                if (!str_contains($f, 'geodata_filter_')) {
                    continue;
                }

                if (Input::post($f)) {
                    $this->config[str_replace('geodata_filter_', '', $f)] = Input::post($f);
                }
            }
        }

        // Retrieve filters
        $template->filters = $this->getFilters();

        // Get the total number of items
        $intTotal = $this->countItems();

        if ($intTotal < 1) {
            return $template->getResponse();
        }

        $this->page = 1;
        $total = $intTotal - $this->offset;

        // Split the results
        if ($this->model->perPage > 0 && (!isset($this->model->limit) || $this->model->numberOfItems > $this->model->perPage)) {
            // Adjust the overall limit
            if (isset($this->limit)) {
                $total = min($this->model->limit, $total);
            }

            // Get the current page
            $id = 'page_n'.$this->model->id;
            $this->page = Input::get($id) ?? 1;

            // Do not index or cache the page if the page number is outside the range
            if ($this->page < 1 || $this->page > max(ceil($total / $this->model->perPage), 1)) {
                throw new PageNotFoundException('Page not found: '.Environment::get('uri'));
            }

            // Set limit and offset
            $this->limit = $this->model->perPage;
            $this->offset += (max($this->page, 1) - 1) * $this->model->perPage;
            $skip = (int) $this->model->skipFirst;

            // Overall limit
            if ($this->model->offset + $this->model->limit > $total + $skip) {
                $this->model->limit = $total + $skip - $this->model->offset;
            }

            // Add the pagination menu
            $objPagination = new Pagination($total, $this->model->perPage, Config::get('maxPaginationLinks'), $id);
            $template->pagination = $objPagination->generate("\n  ");
        }

        $objItems = $this->findItems();

        // Add the items
        if ($objItems instanceof Collection) {
            $template->items = $this->parseItems($objItems);
        }

        $template->moduleId = $this->model->id;

        return $template->getResponse();
    }
}
