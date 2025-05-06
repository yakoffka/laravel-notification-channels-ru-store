<?php
declare(strict_types=1);

namespace NotificationChannels\RuStore\Test\Notifiable;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;

    // public $timestamps = false;

    // protected $fillable = ['email'];

    public function routeNotificationForRuStore()
    {
        return [env('RUSTORE_EXAMPLE_PUSH_TOKEN', 'none')];
    }
}
