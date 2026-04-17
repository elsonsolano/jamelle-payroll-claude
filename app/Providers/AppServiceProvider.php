<?php

namespace App\Providers;

use App\Models\Dtr;
use App\Models\ScheduleChangeRequest;
use Illuminate\Database\Schema\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Builder::defaultStringLength(191);

        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }

        View::composer('layouts.app', function ($view) {
            if (!Auth::check()) {
                $view->with(['pendingOtCount' => 0, 'pendingScheduleCount' => 0]);
                return;
            }
            $view->with([
                'pendingOtCount'       => Dtr::where('status', 'Pending')->orWhere('ot_status', 'pending')->count(),
                'pendingScheduleCount' => ScheduleChangeRequest::where('status', 'pending')->count(),
            ]);
        });
    }
}
