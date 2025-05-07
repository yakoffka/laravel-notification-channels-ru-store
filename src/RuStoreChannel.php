<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use NotificationChannels\RuStore\Reports\RuStoreReport;
use NotificationChannels\RuStore\Reports\RuStoreSingleReport;

class RuStoreChannel
{
    // @todo задействовать проверку максимального объема сообщения
    public const MAX_PAYLOAD_LENGTH = 4096;

    /**
     * @param Dispatcher $events
     * @param RuStoreClient $client
     */
    public function __construct(protected Dispatcher $events, private readonly RuStoreClient $client)
    {
    }

    /**
     * Send the given notification.
     *
     * @param mixed $notifiable
     * @param Notification $notification
     *
     * @return RuStoreReport
     */
    public function send(mixed $notifiable, Notification $notification): RuStoreReport
    {
        // $tokens = Arr::wrap($notifiable->routeNotificationFor('ruStore', $notification));
        //
        // $message = $notification->toRuStore($notifiable);
        //
        // return Collection::make($tokens)
        //     ->map(fn(string $token) => $this->client->send($token, $message))
        //     ->map(fn(RuStoreSendReport $report) => $this->dispatchFailedNotification($notifiable, $notification, $report));


        $message = $notification->toRuStore($notifiable);
        // $tokens = Arr::wrap($notifiable->routeNotificationFor('ruStore', $notification));
        $tokens = Arr::wrap($notifiable->routeNotificationForRuStore());
        $report = $this->client->send($message, $tokens);
        $this->dispatchFailedNotification($notifiable, $notification, $report->getFailure());

        return $report->getSuccess();
        // return $report;
//        dd($report);
//
//        return $report->getResult();

//        return $this->client->sendAll($message, $tokens);
//            ->map(fn(RuStoreSendReport $report) => $this->dispatchFailedNotification($notifiable, $notification, $report));
    }

    /**
     * Поджигание события NotificationFailed
     *
     * @param mixed $notifiable
     * @param Notification $notification
     * @param RuStoreReport $report
     * @return void
     */
    private function dispatchFailedNotification(mixed $notifiable, Notification $notification, RuStoreReport $report): void
    {
        if ($report->all()->isNotEmpty()) {
            $this->events->dispatch(new NotificationFailed($notifiable, $notification, self::class, [
                'report' => $report,
            ]));
        }
    }
}
