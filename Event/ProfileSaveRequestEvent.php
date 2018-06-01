<?php declare(strict_types=1);

namespace Shopware\Storefront\Event;

use Shopware\Application\Context\Struct\ApplicationContext;
use Shopware\Application\Context\Struct\StorefrontContext;
use Shopware\Framework\Event\NestedEvent;
use Shopware\Storefront\Page\Account\ProfileSaveRequest;
use Symfony\Component\HttpFoundation\Request;

class ProfileSaveRequestEvent extends NestedEvent
{
    public const NAME = 'profile.save.request';

    /**
     * @var Request
     */
    private $request;

    /**
     * @var StorefrontContext
     */
    private $context;

    /**
     * @var ProfileSaveRequest
     */
    private $profileSaveRequest;

    public function __construct(Request $request, StorefrontContext $context, ProfileSaveRequest $profileSaveRequest)
    {
        $this->request = $request;
        $this->context = $context;
        $this->profileSaveRequest = $profileSaveRequest;
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContext(): ApplicationContext
    {
        return $this->context->getApplicationContext();
    }

    public function getStorefrontContext(): StorefrontContext
    {
        return $this->context;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getProfileSaveRequest(): ProfileSaveRequest
    {
        return $this->profileSaveRequest;
    }
}
