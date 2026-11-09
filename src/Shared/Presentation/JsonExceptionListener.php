<?php

declare(strict_types=1);

namespace App\Shared\Presentation;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Renders every error as {"error": "..."}, since this is a JSON-only API.
 *
 * Symfony's ErrorListener logs exceptions at priority 0 and renders its own response at -128.
 * Expected client errors from ExceptionStatusMap are answered before the logger (priority 8), so
 * a 404 for an unknown product is not logged as "critical". Everything else is answered after it
 * (priority -64), so real failures are still logged.
 *
 * Unexpected errors never expose their message, whatever the environment: it can contain SQL or
 * other internals. Read the logs instead.
 */
final class JsonExceptionListener
{
    public const MALFORMED_JSON = 'Request body is not valid JSON.';
    public const INTERNAL_ERROR = 'Internal server error.';

    public function __construct(private readonly ExceptionStatusMap $statusMap)
    {
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: 8)]
    public function onClientError(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if (null === $status = $this->statusMap->statusFor($exception)) {
            return;
        }

        // The parser's own wording is an implementation detail, so clients get a fixed message.
        $message = $exception instanceof \JsonException ? self::MALFORMED_JSON : $exception->getMessage();

        $event->setResponse(new JsonResponse(['error' => $message], $status));
    }

    #[AsEventListener(event: KernelEvents::EXCEPTION, priority: -64)]
    public function onOtherError(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();

        if ($exception instanceof HttpExceptionInterface) {
            $event->setResponse(new JsonResponse(
                ['error' => $exception->getMessage()],
                $exception->getStatusCode(),
                $exception->getHeaders(),
            ));

            return;
        }

        $event->setResponse(new JsonResponse(['error' => self::INTERNAL_ERROR], 500));
    }
}
