<?php

declare(strict_types=1);

namespace App\Tests\Shared\Presentation;

use App\Shared\Presentation\Exception\InvalidRequestBody;
use App\Shared\Presentation\ExceptionStatusMap;
use App\Shared\Presentation\JsonExceptionListener;
use App\Shared\Presentation\SharedExceptionStatuses;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;

final class JsonExceptionListenerTest extends TestCase
{
    private JsonExceptionListener $listener;

    protected function setUp(): void
    {
        $this->listener = new JsonExceptionListener(new ExceptionStatusMap([new SharedExceptionStatuses()]));
    }

    public function testAMappedExceptionIsAnsweredWithItsStatusAndMessage(): void
    {
        $event = $this->event(new InvalidRequestBody('"name" must be a string.'));

        $this->listener->onClientError($event);

        self::assertResponse($event->getResponse(), 422, '"name" must be a string.');
    }

    public function testMalformedJsonGetsAFixedMessageInsteadOfTheParserDetail(): void
    {
        $event = $this->event(new \JsonException('Syntax error'));

        $this->listener->onClientError($event);

        self::assertResponse($event->getResponse(), 400, JsonExceptionListener::MALFORMED_JSON);
    }

    public function testAnUnmappedExceptionIsLeftForTheLaterListener(): void
    {
        $event = $this->event(new \RuntimeException('boom'));

        $this->listener->onClientError($event);

        self::assertNull($event->getResponse());
    }

    public function testAnHttpExceptionKeepsItsStatusMessageAndHeaders(): void
    {
        $event = $this->event(new MethodNotAllowedHttpException(['GET', 'POST'], 'Method Not Allowed'));

        $this->listener->onOtherError($event);

        $response = $event->getResponse();
        self::assertResponse($response, 405, 'Method Not Allowed');
        self::assertSame('GET, POST', $response?->headers->get('Allow'));
    }

    public function testAnUnexpectedErrorIsA500ThatHidesItsMessage(): void
    {
        $event = $this->event(new \RuntimeException('SQLSTATE[22003]: Numeric value out of range'));

        $this->listener->onOtherError($event);

        $response = $event->getResponse();
        self::assertResponse($response, 500, JsonExceptionListener::INTERNAL_ERROR);
        self::assertStringNotContainsString('SQLSTATE', (string) $response?->getContent());
    }

    private function event(\Throwable $exception): ExceptionEvent
    {
        return new ExceptionEvent(
            $this->createStub(HttpKernelInterface::class),
            new Request(),
            HttpKernelInterface::MAIN_REQUEST,
            $exception,
        );
    }

    private static function assertResponse(?Response $response, int $status, string $error): void
    {
        self::assertNotNull($response);
        self::assertSame($status, $response->getStatusCode());
        self::assertSame(['error' => $error], json_decode((string) $response->getContent(), true, flags: \JSON_THROW_ON_ERROR));
    }
}
