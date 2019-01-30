<?php declare(strict_types=1);

namespace Shopware\Storefront\Pagelet\Account\PaymentMethod;

use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\Routing\InternalRequest;
use Shopware\Storefront\Framework\Page\PageLoaderInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AccountPaymentMethodPageletLoader implements PageLoaderInterface
{
    /**
     * @var EntityRepositoryInterface
     */
    private $paymentMethodRepository;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(EntityRepositoryInterface $paymentMethodRepository, EventDispatcherInterface $eventDispatcher)
    {
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function load(InternalRequest $request, CheckoutContext $context): EntitySearchResult
    {
        $criteria = $this->createCriteria($request);

        $pagelet = $this->paymentMethodRepository->search($criteria, $context->getContext());

        $this->eventDispatcher->dispatch(
            AccountPaymentMethodPageletLoadedEvent::NAME,
            new AccountPaymentMethodPageletLoadedEvent($pagelet, $context, $request)
        );

        return $pagelet;
    }

    private function createCriteria(InternalRequest $request): Criteria
    {
        $limit = $request->optionalGet('limit', 10);
        $page = $request->optionalGet('p', 1);

        $criteria = new Criteria();
        $criteria->setOffset(($page - 1) * $limit);
        $criteria->setLimit($limit);
        $criteria->setTotalCountMode(Criteria::TOTAL_COUNT_MODE_EXACT);

        return $criteria;
    }
}
