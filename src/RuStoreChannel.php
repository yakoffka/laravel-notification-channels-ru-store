<?php

declare(strict_types=1);

namespace NotificationChannels\RuStore;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;

class RuStoreChannel
{
    /**
     * @param Dispatcher $events
     * @param RuStoreClient $client
     */
    public function __construct(protected Dispatcher $events, private readonly RuStoreClient $client) {}

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     *
     * @return Collection<int, RuStoreSingleReport>
     */
    public function send(mixed $notifiable, Notification $notification): Collection
    {
        $message = $notification->toRuStore($notifiable);
        $tokens = Arr::wrap($notifiable->routeNotificationForRuStore());

        return $this->client->send($message, $tokens)
            ->each(fn(RuStoreSingleReport $report) => $this->dispatchFailedNotification($notifiable, $notification, $report))
            ->filter(fn(RuStoreSingleReport $report) => $report->isSuccess())
            ->values();
    }

    /**
     * Поджигание события NotificationFailed для проваленных отправок
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @param RuStoreSingleReport $report
     * @return void
     */
    private function dispatchFailedNotification(mixed $notifiable, Notification $notification, RuStoreSingleReport $report): void
    {
        if ($report->isFailure()) {
            $this->events->dispatch(new NotificationFailed($notifiable, $notification, self::class, [
                'report' => $report,
            ]));
        }
    }
}
