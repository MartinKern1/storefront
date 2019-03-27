<?php declare(strict_types=1);

namespace Shopware\Storefront\Test;

use PHPUnit\Framework\TestCase;
use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Checkout\Context\CheckoutContextFactory;
use Shopware\Core\Checkout\Customer\Storefront\AccountRegistrationService;
use Shopware\Core\Checkout\Customer\Storefront\AccountService;
use Shopware\Core\Checkout\Exception\CustomerNotFoundException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Exception\ConstraintViolationException;
use Shopware\Core\Framework\Struct\Uuid;
use Shopware\Core\Framework\Test\TestCaseBase\IntegrationTestBehaviour;
use Shopware\Core\Framework\Validation\DataBag\DataBag;

class AccountRegistrationServiceTest extends TestCase
{
    use IntegrationTestBehaviour;

    /**
     * @var AccountRegistrationService
     */
    private $accountRegistrationService;

    /**
     * @var AccountService
     */
    private $accountService;

    /**
     * @var CheckoutContext
     */
    private $checkoutContext;

    protected function setUp(): void
    {
        $this->accountRegistrationService = $this->getContainer()->get(AccountRegistrationService::class);
        $this->accountService = $this->getContainer()->get(AccountService::class);
        $checkoutContextFactory = $this->getContainer()->get(CheckoutContextFactory::class);

        $token = Uuid::uuid4()->getHex();
        $this->checkoutContext = $checkoutContextFactory->create($token, Defaults::SALES_CHANNEL);
    }

    public function testCreateCustomer(): void
    {
        $data = $this->getRegistrationData();

        $customerId = $this->accountRegistrationService->register($data, false, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $customer = $this->accountService->getCustomerByEmail($data->get('email'), $this->checkoutContext);
        static::assertEquals($data->get('lastName'), $customer->getLastName());
        static::assertNotEquals($data->get('password'), $customer->getPassword());
        static::assertNotEmpty($customer->getCustomerNumber());
    }

    public function testCreateWithExistingCustomer(): void
    {
        $data = $this->getRegistrationData();

        $customerId = $this->accountRegistrationService->register($data, false, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $this->expectException(ConstraintViolationException::class);
        $this->accountRegistrationService->register($data, false, $this->checkoutContext);
    }

    public function testCreateGuestWithExistingCustomer(): void
    {
        $data = $this->getRegistrationData();
        $guestData = $this->getRegistrationData(true);

        $customerId = $this->accountRegistrationService->register($data, false, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $customerId = $this->accountRegistrationService->register($guestData, true, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $customers = $this->accountService->getCustomersByEmail($data->get('email'), $this->checkoutContext);
        static::assertCount(2, $customers);

        $customers = $this->accountService->getCustomersByEmail($data->get('email'), $this->checkoutContext, false);
        static::assertCount(1, $customers);

        $this->expectException(CustomerNotFoundException::class);
        $this->accountService->getCustomerByEmail($data->get('email'), $this->checkoutContext, true);
    }

    public function testLoginWithAdditionalGuestAccount(): void
    {
        $guestData = $this->getRegistrationData(true);
        $data = $this->getRegistrationData();

        $customerId = $this->accountRegistrationService->register($guestData, true, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $customerId = $this->accountRegistrationService->register($data, false, $this->checkoutContext);
        static::assertNotEmpty($customerId);

        $customer = $this->accountService->getCustomerByEmail($data->get('email'), $this->checkoutContext);
        static::assertEquals($data->get('lastName'), $customer->getLastName());
    }

    private function getRegistrationData($isGuest = false): DataBag
    {
        $data = [
            'email' => 'max.mustermann@example.com',
            'salutationId' => Defaults::SALUTATION_ID_MR,
            'firstName' => 'Max',
            'lastName' => 'Mustermann',

            'billingAddress' => [
                'countryId' => Defaults::COUNTRY,
                'street' => 'Musterstrasse 13',
                'zipcode' => '48599',
                'city' => 'Epe',
            ],
        ];

        if (!$isGuest) {
            $data['password'] = Uuid::uuid4()->getHex();
        }

        return new DataBag($data);
    }
}
