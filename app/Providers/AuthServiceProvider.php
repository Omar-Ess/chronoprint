<?php

namespace App\Providers;

use App\Models\Cart;
use App\Models\User;
use App\Models\Address;
use Illuminate\Support\Facades\Gate;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        Gate::define("checkout", function (User $user, Cart $cart) {
            return count($cart->items);
        });

        Gate::define("delete_address", function (User $user, Address $address) {
            return $address->user_id == $user->id;
        });
    }
}
