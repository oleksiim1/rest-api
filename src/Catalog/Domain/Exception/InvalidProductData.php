<?php

declare(strict_types=1);

namespace App\Catalog\Domain\Exception;

/**
 * A value supplied for a product breaks a domain rule.
 */
class InvalidProductData extends \DomainException
{
}
