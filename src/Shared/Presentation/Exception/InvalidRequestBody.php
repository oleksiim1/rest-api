<?php

declare(strict_types=1);

namespace App\Shared\Presentation\Exception;

/**
 * The request body is valid JSON but does not have the expected shape (missing keys, wrong types).
 */
final class InvalidRequestBody extends \RuntimeException
{
}
