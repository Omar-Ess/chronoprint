<?php

namespace App\Providers;

use App\Models\CartItem;
use App\Events\OrderPlaced;
use App\Events\OrderStatusChanged;
use App\Observers\CartItemObserver;
use Illuminate\Auth\Events\Registered;
use App\Listeners\SendToUserOrderPlacedNotification;
use App\Listeners\SendToAdminsOrderPlacedNotification;
use App\Listeners\SendToUserOrderStatusChangedNotification;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        OrderPlaced::class => [
            SendToUserOrderPlacedNotification::class,
            SendToAdminsOrderPlacedNotification::class
        ],
        OrderStatusChanged::class => [
            SendToUserOrderStatusChangedNotification::class
        ]
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        CartItem::observe(CartItemObserver::class);
    }
}
