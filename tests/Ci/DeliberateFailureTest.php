<?php

declare(strict_types=1);

namespace App\Tests\Ci;

use PHPUnit\Framework\TestCase;

final class DeliberateFailureTest extends TestCase
{
    public function testFailsOnPurpose(): void
    {
        self::fail('Temporary failure to verify the CI check goes red.');
    }
}
