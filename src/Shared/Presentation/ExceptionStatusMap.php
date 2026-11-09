<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use App\Catalog\Domain\Exception\InvalidProductData;
use App\Catalog\Domain\Exception\InvalidProductId;
use App\Catalog\Domain\Exception\ProductAlreadyExists;
use App\Catalog\Domain\Exception\ProductNotFound;
use App\Shared\Presentation\Exception\InvalidRequestBody;

/**
 * HTTP status for each exception the API exposes to clients. Their messages are safe to show.
 * Add a bounded context's domain exceptions here when it gets endpoints.
 */
final class ExceptionStatusMap
{
    /** @var array<class-string<\Throwable>, int> */
    private const MAP = [
        \JsonException::class => 400,
        InvalidRequestBody::class => 422,

        // Catalog
        ProductNotFound::class => 404,
        InvalidProductId::class => 404,
        ProductAlreadyExists::class => 409,
        InvalidProductData::class => 422,
    ];

    public function statusFor(\Throwable $exception): ?int
    {
        foreach (self::MAP as $class => $status) {
            if ($exception instanceof $class) {
                return $status;
            }
        }

        return null;
    }
}
