<?php declare(strict_types=1);

namespace Shopware\Storefront\PageController;

use Shopware\Storefront\Framework\Controller\StorefrontController;
use Shopware\Storefront\Framework\Twig\ErrorTemplateResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ErrorPageController extends StorefrontController
{
    /**
     * @var ErrorTemplateResolver
     */
    protected $errorTemplateResolver;

    public function __construct(ErrorTemplateResolver $errorTemplateResolver)
    {
        $this->errorTemplateResolver = $errorTemplateResolver;
    }

    public function error(\Exception $exception, Request $request): Response
    {
        $response = $this->forward("Shopware\Storefront\PageController\HomePageController:index");

        switch ((int) $exception->getCode()) {
            case 404:
                $code = 404;
                break;
            default: $code = 500;
        }

        $response->setStatusCode($code);

        return $response;
    }
}
