<?php declare(strict_types=1);

namespace Shopware\Storefront\Api\Seo\Event\SeoUrl;

use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Storefront\Api\Seo\Struct\SeoUrlSearchResult;

class SeoUrlSearchResultLoadedEvent extends NestedEvent
{
    public const NAME = 'seo_url.search.result.loaded';

    /**
     * @var SeoUrlSearchResult
     */
    protected $result;

    public function __construct(SeoUrlSearchResult $result)
    {
        $this->result = $result;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ApplicationContext
    {
        return $this->result->getContext();
    }
}
