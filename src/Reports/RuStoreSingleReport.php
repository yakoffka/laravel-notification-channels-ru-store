<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Reports;

use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\Response;
use Throwable;

/**
 * Отчет об отправке уведомления на одно из устройств пользователя
 */
final readonly class RuStoreSingleReport
{
    /**
     * @param string $target
     * @param PromiseInterface|Response|null $response
     * @param Throwable|null $error
     */
    public function __construct(
        private string                         $target,
        private PromiseInterface|Response|null $response = null,
        private ?Throwable                 $error = null,
    ) {}

    /**
     * Создание успешного отчета
     *
     * @param string $target
     * @param PromiseInterface|Response $response
     * @return self
     */
    public static function success(string $target, PromiseInterface|Response $response): self
    {
        return new self(
            target: $target,
            response: $response,
        );
    }

    /**
     * Создание отчета об ошибке
     *
     * @param string $target
     * @param Throwable $error
     * @param PromiseInterface|Response|null $response
     * @return self
     */
    public static function failure(string $target, Throwable $error, PromiseInterface|Response|null $response = null): self
    {
        return new self(
            target: $target,
            response: $response,
            error: $error,
        );
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->error === null;
    }

    /**
     * @return bool
     */
    public function isFailure(): bool
    {
        return ! $this->isSuccess();
    }

    /**
     * @return string
     */
    public function target(): string
    {
        return $this->target;
    }

    /**
     * @return PromiseInterface|Response|null
     */
    public function response(): PromiseInterface|Response|null
    {
        return $this->response;
    }

    /**
     * @return Throwable|null
     */
    public function error(): ?Throwable
    {
        return $this->error;
    }
}
