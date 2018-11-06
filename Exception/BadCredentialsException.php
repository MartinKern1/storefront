<?php declare(strict_types=1);

namespace Shopware\Storefront\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class BadCredentialsException extends ShopwareHttpException
{
    protected $code = 'AUTH-BAD_CREDENTIALS';

    public function __construct(int $code = 0, Throwable $previous = null)
    {
        parent::__construct('Invalid username and/or password.', $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_UNAUTHORIZED;
    }
}
