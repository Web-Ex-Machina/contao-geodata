<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Controller\Frontend;

use Contao\Controller;
use Contao\CoreBundle\DependencyInjection\Attribute\AsFrontendModule;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\Input;
use Contao\Model\Collection;
use Contao\ModuleModel;
use Contao\Template;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\UtilsBundle\Classes\DcaUtil;
use WEM\UtilsBundle\Classes\StringUtil;

#[AsFrontendModule(
    FiltersController::TYPE, 
    category: 'wem_geodata',
    template: 'mod_wem_geodata_filters'
)]
class FiltersController extends ModuleController
{
    public const TYPE = 'wem_geodata_filters';

    protected ?array $baseConfig = [];
    protected array $filters = [];

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
        $this->model = $model;
        $this->loadMap();

        // Add pids
        $this->config = [
            'pid' => $this->map->id,
            'published' => 1,
        ];
        $this->baseConfig = $this->config;

        // Retrieve filters
        $this->buildFilters();

        // Add search filter
        $this->addSearchFilter();

        $template->filters = $this->filters;
        $template->moduleId = $this->model->id;

        // Define where the form is redirected
        if ($this->model->jumpTo) {
            $page = PageModel::findById($this->model->jumpTo);
            $template->formAction = $this->contentUrlGenerator->generate($page);
        } else {
            $template->formAction = $request->getRequestUri();
        }

        // Reset link
        if ($this->config !== $this->baseConfig) {
            $template->formReset = $request->getPathInfo();
        }

        return $template->getResponse();
    }

    /**
     * Retrieve filters.
     */
    protected function getFilters(): array
    {
        return StringUtil::deserialize($this->model->wem_geodata_filters_fields);
    }

    /**
     * Retrieve filter options
     */
    protected function getFilterOptions(string $f): Collection
    {
        return MapItem::findItems($this->baseConfig, 0, 0, ['group' => $f]);
    }

    /**
     * Retrieve list filters.
     */
    protected function buildFilters(): void
    {
        // Retrieve and format dropdowns filters
        $filters = $this->getFilters();

        if (\is_array($filters) && [] !== $filters) {
            foreach ($filters as $f) {
                if ($this->shouldBeSkipped($f . ' != ""')) {
                    continue;
                }

                $this->addFilter($f);
            }
        }
    }

    protected function addFilter(string $f): void
    {
        $field = DcaUtil::getFieldConfig($f, MapItem::getTable());

        $fName = \sprintf(
            'geodata_filter_%s%s', 
            $f, 
            DcaUtil::isFieldMultiple($field) ? '[]' : ''
        );
        $fGet = \sprintf('geodata_filter_%s', $f);

        $filter = [
            'type' => $field['inputType'],
            'name' => $fName,
            'label' => $field['label'][0] ?? $f,
            'value' => Input::get($fGet) ?: '',
            'options' => [],
            'multiple' => DcaUtil::isFieldMultiple($field),
        ];

        switch ($field['inputType']) {
            case 'select':
                if (\array_key_exists('options_callback', $field) && \is_array($field['options_callback'])) {
                    $strClass = $field['options_callback'][0];
                    $strMethod = $field['options_callback'][1];

                    $callback = new $strClass();
                    $options = $callback->{$strMethod}($this->map);
                } elseif (\array_key_exists('options_callback', $field) && \is_callable($field['options_callback'])) {
                    $options = $field['options_callback']($this);
                } else {
                    $opts = $field['options'];

                    if (is_array($opts) && !empty($opts)) {

                        // There are two types of array 
                        // classic key - value
                        // contao arr[value] - arr[label]
                        if (\is_array(array_first($opts)) && \array_key_exists('value', array_first($opts))) {
                            foreach($opts as $opt) {
                                $options[$opt['value']] = $opt['label'];
                            }
                        } else {
                            $options = $opts;
                        }        
                    }
                }

                foreach ($options as $value => $label) {
                    if (\is_array($label)) {
                        foreach ($label as $subValue => $subLabel) {

                            $statement = DcaUtil::isFieldMultiple($field) 
                                ? $f . ' LIKE "%%'. $subValue .'%%"' 
                                : $f . ' = "'. $subValue .'"'
                            ;

                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$value]['options'][] = [
                                'value' => $subValue,
                                'label' => $subLabel,
                                'selected' => $this->isOptionSelected($fGet, $subValue, DcaUtil::isFieldMultiple($field)),
                            ];
                        }
                    } else {
                        $statement = DcaUtil::isFieldMultiple($field) 
                            ? $f . ' LIKE "%%'. $value .'%%"' 
                            : $f . ' = "'. $value .'"'
                        ;
                        
                        if ($this->shouldBeSkipped($statement)) {
                            return;
                        }

                        $filter['options'][] = [
                            'value' => $value,
                            'label' => $label,
                            'selected' => $this->isOptionSelected($fGet, $value, DcaUtil::isFieldMultiple($field)),
                        ];
                    }
                }

                break;

            case 'listWizard':
                $objOptions = $this->getFilterOptions($f);

                if ($objOptions) {
                    $filter['type'] = 'select';
                    if (DcaUtil::isFieldMultiple($field)) {
                        $filter['name'] .= '[]';
                    }

                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            return;
                        }

                        $subOptions = StringUtil::deserialize($objOptions->{$f});
                        foreach ($subOptions as $subOption) {
                            $statement = DcaUtil::isFieldMultiple($field) 
                                ? $f . ' LIKE "%%'. $subOption .'%%"' 
                                : $f . ' = "'. $subOption .'"'
                            ;
                            
                            if ($this->shouldBeSkipped($statement)) {
                                return;
                            }

                            $filter['options'][$subOption] = [
                                'value' => $subOption,
                                'label' => $subOption,
                                'selected' => $this->isOptionSelected($fGet, $subOption, DcaUtil::isFieldMultiple($field)),
                            ];
                        }
                    }
                }

                break;

            case 'text':
            default:
                $objOptions = $this->getFilterOptions($f);


                if ($objOptions && 0 < $objOptions->count()) {
                    $filter['type'] = 'select';
                    while ($objOptions->next()) {
                        if (!$objOptions->{$f}) {
                            continue;
                        }

                        if ($this->shouldBeSkipped($f . ' = "'. $objOptions->{$f} .'"')) {
                            continue;
                        }

                        $filter['options'][] = [
                            'value' => $objOptions->{$f},
                            'label' => $objOptions->{$f},
                            'selected' => $this->isOptionSelected($fGet, $objOptions->{$f}, DcaUtil::isFieldMultiple($field)),
                        ];
                    }
                }

                break;
        }

        if ('select' === $filter['type'] && 1 >= \count($filter['options'])) {
            return;
        }

        if (null !== Input::get($fName) && '' !== Input::get($fName)) {
            $this->config[$f] = Input::get($fName);
        }

        $this->filters[] = $filter;
    }

    protected function isOptionSelected(string $f, string|int $v, bool $multiple = false) {
        return $multiple
            ? (null !== Input::get($f) && \in_array((string) $v, Input::get($f ?? []), true))
            : (null !== Input::get($f) && Input::get($f) === (string) $v)
        ;
    }

    // Add fulltext search if asked
    protected function addSearchFilter(): void
    {
        if ($this->model->wem_geodata_addSearch) {
            $this->filters[] = [
                'type' => 'text',
                'name' => 'geodata_filter_search',
                'label' => $GLOBALS['TL_LANG']['WEM']['GEODATA']['search'],
                'placeholder' => $GLOBALS['TL_LANG']['WEM']['GEODATA']['searchPlaceholder'],
                'value' => Input::get('geodata_filter_search') ?: '',
            ];

            if ('' !== Input::get('geodata_filter_search') && null !== Input::get('geodata_filter_search')) {
                $this->config['geodata_filter_search'] = StringUtil::formatKeywords(Input::get('geodata_filter_search'));
            }
        }
    }

    protected function shouldBeSkipped($statement): bool
    {
        if (!$this->model->wem_geodata_hideFiltersWithNoResults) {
            return false;
        }

        $config = $this->config;
        $config['where'][] = $statement;

        return 0 === $this->countItems($config);
    }
}
