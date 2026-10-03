<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\Paginator;
use App\Services\FirebaseNotificationService;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(FirebaseNotificationService::class, function () {
            return new FirebaseNotificationService();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
	{
		Paginator::useBootstrap();

		// Manually include the helper file
		if (file_exists(app_path('Helpers/langHelper.php'))) {
			require_once app_path('Helpers/langHelper.php');
		}
	}
}
