<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore\Test;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

class TestNotifiableModel extends Authenticatable
{
    use Notifiable;

    private ?array $tokens = null;

    public function setTokens(?array $tokens = null): self
    {
        $this->tokens = $tokens ?? [Str::random(32)];

        return $this;
    }

    public function unsetTokens(): self
    {
        $this->tokens = [];

        return $this;
    }

    /**
     * @return array
     */
    public function routeNotificationForRuStore(): array
    {
        return $this->tokens ?? [];
    }
}
