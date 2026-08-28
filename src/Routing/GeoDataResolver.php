<?php

declare(strict_types=1);

namespace WEM\GeoDataBundle\Routing;

use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\CoreBundle\Routing\Content\ContentUrlResolverInterface;
use Contao\CoreBundle\Routing\Content\ContentUrlResult;
use Contao\PageModel;
use Contao\System;
use Terminal42\ChangeLanguage\PageFinder;
use Symfony\Component\HttpFoundation\RequestStack;
use WEM\GeoDataBundle\Model\MapItem;
use WEM\GeoDataBundle\Model\Map;

class GeoDataResolver implements ContentUrlResolverInterface
{
    public function __construct(
        private readonly ContaoFramework $framework,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function resolve(object $content): ContentUrlResult|null
    {
        if (!$content instanceof MapItem) {
            return null;
        }

        $pageAdapter = $this->framework->getAdapter(PageModel::class);
        $archiveAdapter = $this->framework->getAdapter(Map::class);
        $locale = $this->requestStack->getCurrentRequest()->getLocale();

        $objMaster = $pageAdapter->findById((int) $archiveAdapter->findById($content->pid)?->jumpTo);
        
        if ($objMaster) {
            $objTarget = (new PageFinder())->findAssociatedForLanguage($objMaster, $locale);
        } else {
            $objTarget = $objMaster;
        }

        // Link to the default page
        return ContentUrlResult::resolve($objTarget);
    }

    public function getParametersForContent(object $content, PageModel $pageModel): array
    {
        if (!$content instanceof MapItem) {
            return [];
        }

        $params = \sprintf(
            '/%s',
            $content->alias ?: $content->id,
        );

        return ['parameters' => $params];
    }
}
