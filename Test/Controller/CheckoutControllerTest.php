<?php declare(strict_types=1);

namespace Shopware\Storefront\Test\Controller;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Content\Product\Aggregate\ProductVisibility\ProductVisibilityDefinition;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextFactory;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\CheckoutController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckoutControllerTest extends TestCase
{
    use IntegrationTestBehaviour;

    private const UUID_LENGTH = 32;
    private const PRODUCT_PRICE = 15.99;
    private const CUSTOMER_NAME = 'Tester';

    /**
     * @dataProvider customerComments
     *
     * @param string|float|int|bool|null $customerComment
     */
    public function testOrderCustomerComment($customerComment, ?string $savedCustomerComment): void
    {
        $order = $this->performOrder($customerComment);
        static::assertSame($savedCustomerComment, $order->getCustomerComment());
    }

    public function customerComments(): array
    {
        return [
            ["  Hello, \nthis is a customer comment!  ", "Hello, \nthis is a customer comment!"],
            ['<script>alert("hello")</script>', 'alert("hello")'],
            ['<h1>Hello</h1><br><br>This is a Test! ', 'HelloThis is a Test!'],
            ['  ', null],
            ['', null],
            [1.2, '1.2'],
            [12, '12'],
            [true, '1'],
            [false, null],
            [null, null],
        ];
    }

    public function testOrder(): void
    {
        $order = $this->performOrder('');

        static::assertSame(self::PRODUCT_PRICE, $order->getPrice()->getTotalPrice());
        static::assertSame(self::CUSTOMER_NAME, $order->getOrderCustomer()->getLastName());
    }

    /**
     * @param string|float|int|bool|null $customerComment
     */
    private function performOrder($customerComment): OrderEntity
    {
        $contextToken = Uuid::randomHex();

        $this->fillCart($contextToken);

        $requestDataBag = $this->createRequestDataBag($customerComment);
        $salesChannelContext = $this->createSalesChannelContext($contextToken);
        $request = $this->createRequest();

        /** @var RedirectResponse|Response $response */
        $response = $this->getContainer()->get(CheckoutController::class)->order($requestDataBag, $salesChannelContext, $request);

        static::assertInstanceOf(RedirectResponse::class, $response);

        $orderId = substr($response->getTargetUrl(), -self::UUID_LENGTH);

        /** @var EntityRepositoryInterface $orderRepo */
        $orderRepo = $this->getContainer()->get('order.repository');

        /** @var OrderEntity|null $order */
        $order = $orderRepo->search(new Criteria([$orderId]), Context::createDefaultContext())->first();

        static::assertNotNull($order);

        return $order;
    }

    private function createCustomer(): string
    {
        $customerId = Uuid::randomHex();
        $salutationId = $this->getValidSalutationId();
        $paymentMethodId = $this->getValidPaymentMethodId();

        $customer = [
            [
                'id' => $customerId,
                'salesChannelId' => Defaults::SALES_CHANNEL,
                'defaultShippingAddress' => [
                    'id' => $customerId,
                    'firstName' => 'Test',
                    'lastName' => self::CUSTOMER_NAME,
                    'city' => 'Schöppingen',
                    'street' => 'Ebbinghoff 10',
                    'zipcode' => '48624',
                    'salutationId' => $salutationId,
                    'countryId' => $this->getValidCountryId(),
                ],
                'defaultBillingAddressId' => $customerId,
                'defaultPaymentMethodId' => $paymentMethodId,
                'groupId' => Defaults::FALLBACK_CUSTOMER_GROUP,
                'email' => Uuid::randomHex() . '@example.com',
                'password' => 'not',
                'firstName' => 'Test',
                'lastName' => self::CUSTOMER_NAME,
                'salutationId' => $salutationId,
                'customerNumber' => '12345',
            ],
        ];

        $this->getContainer()->get('customer.repository')->create($customer, Context::createDefaultContext());

        return $customerId;
    }

    private function createProduct(): string
    {
        $productId = Uuid::randomHex();

        $product = [
            'id' => $productId,
            'name' => 'Test product',
            'productNumber' => '123456789',
            'stock' => 1,
            'price' => [
                ['currencyId' => Defaults::CURRENCY, 'gross' => self::PRODUCT_PRICE, 'net' => 10, 'linked' => false],
            ],
            'manufacturer' => ['id' => $productId, 'name' => 'shopware AG'],
            'tax' => ['id' => $productId, 'name' => 'testTaxRate', 'taxRate' => 15],
            'categories' => [
                ['id' => $productId, 'name' => 'Test category'],
            ],
            'visibilities' => [
                [
                    'id' => $productId,
                    'salesChannelId' => Defaults::SALES_CHANNEL,
                    'visibility' => ProductVisibilityDefinition::VISIBILITY_ALL,
                ],
            ],
        ];

        $this->getContainer()->get('product.repository')->create([$product], Context::createDefaultContext());

        return $productId;
    }

    private function fillCart(string $contextToken): void
    {
        $cart = $this->getContainer()->get(CartService::class)->createNew($contextToken);

        $productId = $this->createProduct();
        $cart->add(new LineItem('lineItem1', LineItem::PRODUCT_LINE_ITEM_TYPE, $productId));
    }

    private function createRequestDataBag($customerComment): RequestDataBag
    {
        return new RequestDataBag(['tos' => true, 'customerComment' => $customerComment]);
    }

    private function createSalesChannelContext(string $contextToken): SalesChannelContext
    {
        return $this->getContainer()->get(SalesChannelContextFactory::class)->create(
            $contextToken,
            Defaults::SALES_CHANNEL,
            [SalesChannelContextService::CUSTOMER_ID => $this->createCustomer()]
        );
    }

    private function createRequest(): Request
    {
        $request = new Request();
        $request->setSession($this->getContainer()->get('session'));

        return $request;
    }
}
