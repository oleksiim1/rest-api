<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Each bounded context implements this in its Presentation layer to declare which of its
 * exceptions are client errors and their HTTP status. Their messages must be safe to show.
 */
#[AutoconfigureTag]
interface ExceptionStatusMapping
{
    /**
     * @return array<class-string<\Throwable>, int>
     */
    public function statuses(): array;
}
