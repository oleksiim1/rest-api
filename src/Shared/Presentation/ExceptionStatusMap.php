<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * HTTP status for each exception the API exposes to clients, collected from every
 * ExceptionStatusMapping. The most specific mapped class in an exception's hierarchy wins.
 */
final class ExceptionStatusMap
{
    /** @var array<class-string<\Throwable>, int> */
    private readonly array $statuses;

    /**
     * @param iterable<ExceptionStatusMapping> $mappings
     */
    public function __construct(
        #[AutowireIterator(ExceptionStatusMapping::class)]
        iterable $mappings,
    ) {
        $statuses = [];
        foreach ($mappings as $mapping) {
            foreach ($mapping->statuses() as $class => $status) {
                if (isset($statuses[$class])) {
                    throw new \LogicException(\sprintf('"%s" is mapped to an HTTP status more than once.', $class));
                }
                $statuses[$class] = $status;
            }
        }

        $this->statuses = $statuses;
    }

    public function statusFor(\Throwable $exception): ?int
    {
        for ($class = $exception::class; false !== $class; $class = get_parent_class($class)) {
            if (isset($this->statuses[$class])) {
                return $this->statuses[$class];
            }
        }

        return null;
    }
}
