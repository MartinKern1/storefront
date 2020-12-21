<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\Product\Review;

use Shopware\Core\Content\Product\Aggregate\ProductReview\ProductReviewEntity;
use Shopware\Core\Content\Product\SalesChannel\Review\AbstractProductReviewRoute;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\FilterAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Aggregation\Bucket\TermsAggregation;
use Shopware\Core\Framework\DataAbstractionLayer\Search\AggregationResult\Bucket\TermsResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Routing\Exception\MissingRequestParameterException;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Framework\Page\StorefrontSearchResult;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class ProductReviewLoader
{
    private const LIMIT = 10;
    private const DEFAULT_PAGE = 1;
    private const FILTER_LANGUAGE = 'filter-language';

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var AbstractProductReviewRoute
     */
    private $route;

    public function __construct(
        AbstractProductReviewRoute $route,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->route = $route;
    }

    /**
     * load reviews for one product. The request must contain the productId
     * otherwise MissingRequestParameterException is thrown
     *
     * @throws MissingRequestParameterException
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function load(Request $request, SalesChannelContext $context): ReviewLoaderResult
    {
        $productId = $request->get('parentId') ?? $request->get('productId');
        if (!$productId) {
            throw new MissingRequestParameterException('productId');
        }

        $criteria = $this->createCriteria($request, $context);

        $reviews = $this->route
            ->load($productId, $request, $context, $criteria)
            ->getResult();

        $reviews = StorefrontSearchResult::createFrom($reviews);

        $this->eventDispatcher->dispatch(new ProductReviewsLoadedEvent($reviews, $context, $request));

        $reviewResult = ReviewLoaderResult::createFrom($reviews);
        $reviewResult->setProductId($request->get('productId'));
        $reviewResult->setParentId($request->get('parentId'));

        $aggregation = $reviews->getAggregations()->get('ratingMatrix');
        $matrix = new RatingMatrix([]);

        if ($aggregation instanceof TermsResult) {
            $matrix = new RatingMatrix($aggregation->getBuckets());
        }
        $reviewResult->setMatrix($matrix);
        $reviewResult->setCustomerReview($this->getCustomerReview($productId, $context));
        $reviewResult->setTotalReviews($matrix->getTotalReviewCount());

        return $reviewResult;
    }

    private function createCriteria(Request $request, SalesChannelContext $context): Criteria
    {
        $limit = (int) $request->get('limit', self::LIMIT);
        $page = (int) $request->get('p', self::DEFAULT_PAGE);
        $offset = $limit * ($page - 1);

        $criteria = new Criteria();
        $criteria->setLimit($limit);
        $criteria->setOffset($offset);

        $sorting = new FieldSorting('createdAt', 'DESC');
        if ($request->get('sort', 'points') === 'points') {
            $sorting = new FieldSorting('points', 'DESC');
        }

        $criteria->addSorting($sorting);

        if ($request->get('language') === self::FILTER_LANGUAGE) {
            $criteria->addPostFilter(
                new EqualsFilter('languageId', $context->getContext()->getLanguageId())
            );
        }

        $this->handlePointsAggregation($request, $criteria, $context);

        return $criteria;
    }

    /**
     * get review by productId and customer
     * a customer should only create one review per product, so if there are more than one
     * review we only take one
     *
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    private function getCustomerReview(string $productId, SalesChannelContext $context): ?ProductReviewEntity
    {
        $customer = $context->getCustomer();

        if (!$customer) {
            return null;
        }

        $criteria = new Criteria();
        $criteria->setLimit(1);
        $criteria->setOffset(0);
        $criteria->addFilter(new EqualsFilter('customerId', $customer->getId()));

        $customerReviews = $this->route
            ->load($productId, new Request(), $context, $criteria)
            ->getResult();

        return $customerReviews->first();
    }

    private function handlePointsAggregation(Request $request, Criteria $criteria, SalesChannelContext $context): void
    {
        $points = $request->get('points', []);

        if (\is_array($points) && \count($points) > 0) {
            $pointFilter = [];
            foreach ($points as $point) {
                $pointFilter[] = new RangeFilter('points', [
                    'gte' => $point - 0.5,
                    'lt' => $point + 0.5,
                ]);
            }

            $criteria->addPostFilter(new MultiFilter(MultiFilter::CONNECTION_OR, $pointFilter));
        }

        $reviewFilters[] = new EqualsFilter('status', true);
        if ($context->getCustomer() !== null) {
            $reviewFilters[] = new EqualsFilter('customerId', $context->getCustomer()->getId());
        }

        $criteria->addAggregation(
            new FilterAggregation(
                'customer-login-filter',
                new TermsAggregation('ratingMatrix', 'points'),
                [
                    new MultiFilter(MultiFilter::CONNECTION_OR, $reviewFilters),
                ]
            )
        );
    }
}
