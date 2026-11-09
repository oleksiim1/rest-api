<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use App\Shared\Presentation\Exception\InvalidQueryParameter;
use App\Shared\Presentation\Exception\InvalidRequestBody;

final class SharedExceptionStatuses implements ExceptionStatusMapping
{
    public function statuses(): array
    {
        return [
            \JsonException::class => 400,
            InvalidQueryParameter::class => 400,
            InvalidRequestBody::class => 422,
        ];
    }
}
