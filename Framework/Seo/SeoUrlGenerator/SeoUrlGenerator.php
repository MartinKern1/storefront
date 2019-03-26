<?php declare(strict_types=1);

namespace Shopware\Storefront\Framework\Seo\SeoUrlGenerator;

use Cocur\Slugify\Bridge\Twig\SlugifyExtension;
use Cocur\Slugify\Slugify;
use Shopware\Core\Checkout\Context\CheckoutContextFactoryInterface;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

abstract class SeoUrlGenerator implements SeoUrlGeneratorInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @var Environment
     */
    protected $twig;

    /**
     * @var string
     */
    protected $routeName;

    /**
     * @var CheckoutContextFactoryInterface
     */
    protected $checkoutContextFactory;

    /**
     * @var EntityRepositoryInterface
     */
    protected $salesChannelRepository;

    public function __construct(EntityRepositoryInterface $salesChannelRepository, CheckoutContextFactoryInterface $checkoutContextFactory, Slugify $slugify, RouterInterface $router, string $routeName)
    {
        $this->twig = new Environment(new ArrayLoader());
        $this->twig->setCache(false);
        $this->twig->enableStrictVariables();
        $this->twig->addExtension(new SlugifyExtension($slugify));

        $this->checkoutContextFactory = $checkoutContextFactory;
        $this->salesChannelRepository = $salesChannelRepository;

        $this->router = $router;
        $this->routeName = $routeName;
        if (!$this->router->getRouteCollection()->get($this->routeName)) {
            throw new \InvalidArgumentException('Route ' . $this->routeName . ' not found');
        }
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    protected function getContext(string $salesChannelId): Context
    {
        /** @var SalesChannelEntity $salesChannel */
        $salesChannel = $this->salesChannelRepository
            ->search(new Criteria([$salesChannelId]), Context::createDefaultContext())
            ->first();
        $options = $salesChannel->jsonSerialize();

        $checkoutContext = $this->checkoutContextFactory->create(
            Uuid::uuid4()->getHex(),
            $salesChannelId,
            $options
        );

        return $checkoutContext->getContext();
    }
}
