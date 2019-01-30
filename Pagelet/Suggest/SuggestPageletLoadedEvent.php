<?php declare(strict_types=1);

namespace Shopware\Storefront\Pagelet\Suggest;

use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;
use Shopware\Core\Framework\Routing\InternalRequest;

class SuggestPageletLoadedEvent extends NestedEvent
{
    public const NAME = 'suggest.pagelet.loaded';

    /**
     * @var SuggestPagelet
     */
    protected $page;

    /**
     * @var CheckoutContext
     */
    protected $context;

    /**
     * @var InternalRequest
     */
    protected $request;

    public function __construct(SuggestPagelet $page, CheckoutContext $context, InternalRequest $request)
    {
        $this->page = $page;
        $this->context = $context;
        $this->request = $request;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): Context
    {
        return $this->context->getContext();
    }

    public function getCheckoutContext(): CheckoutContext
    {
        return $this->context;
    }

    public function getPage(): SuggestPagelet
    {
        return $this->page;
    }

    public function getRequest(): InternalRequest
    {
        return $this->request;
    }
}
