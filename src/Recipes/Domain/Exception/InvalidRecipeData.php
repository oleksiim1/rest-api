<?php

declare(strict_types=1);

namespace App\Recipes\Domain\Exception;

/**
 * A value supplied for a recipe breaks a domain rule.
 */
class InvalidRecipeData extends \DomainException
{
}
