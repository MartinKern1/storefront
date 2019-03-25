<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Checkout\Confirm;

use Shopware\Core\Checkout\Cart\Storefront\CartService;
use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Checkout\Payment\PaymentMethodCollection;
use Shopware\Core\Checkout\Shipping\ShippingMethodCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Routing\InternalRequest;
use Shopware\Storefront\Framework\Page\PageLoaderInterface;
use Shopware\Storefront\Framework\Page\PageWithHeaderLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class CheckoutConfirmPageLoader implements PageLoaderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var EntityRepositoryInterface
     */
    private $shippingMethodRepository;

    /**
     * @var PageWithHeaderLoader|PageLoaderInterface
     */
    private $genericLoader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var CartService
     */
    private $cartService;

    public function __construct(
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $shippingMethodRepository,
        PageLoaderInterface $genericLoader,
        EventDispatcherInterface $eventDispatcher,
        CartService $cartService
    ) {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->shippingMethodRepository = $shippingMethodRepository;
        $this->genericLoader = $genericLoader;
        $this->eventDispatcher = $eventDispatcher;
        $this->cartService = $cartService;
    }

    public function load(InternalRequest $request, CheckoutContext $context)
    {
        $page = $this->genericLoader->load($request, $context);

        $page = CheckoutConfirmPage::createFrom($page);

        $page->setCart($this->cartService->getCart($context->getToken(), $context));

        $page->setPaymentMethods($this->getPaymentMethods($context));

        $page->setShippingMethods($this->getShippingMethods($context));

        $this->eventDispatcher->dispatch(
            CheckoutConfirmPageLoadedEvent::NAME,
            new CheckoutConfirmPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }

    private function getPaymentMethods(CheckoutContext $context): PaymentMethodCollection
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
        /** @var PaymentMethodCollection $paymentMethods */
        $paymentMethods = $this->paymentMethodRepository->search($criteria, $context->getContext())->getEntities();

        return $paymentMethods;
    }

    private function getShippingMethods(CheckoutContext $context): ShippingMethodCollection
    {
        $criteria = (new Criteria())->addFilter(new EqualsFilter('active', true));
        /** @var ShippingMethodCollection $shippingMethods */
        $shippingMethods = $this->shippingMethodRepository->search($criteria, $context->getContext())->getEntities();

        return $shippingMethods->filterByActiveRules($context);
    }
}
