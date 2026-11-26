<?php

namespace App\Providers;

use App\Models\Meeting;
use App\Models\Tag;
use App\Models\User;
use App\Observers\MeetingObserver;
use App\Observers\TagObserver;
use App\Observers\UserObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Meeting::observe(MeetingObserver::class);
        Tag::observe(TagObserver::class);
        User::observe(UserObserver::class);
    }
}
