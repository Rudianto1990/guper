<?php


namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use App\Models\Pengaturan;

class AppServiceProvider extends ServiceProvider
{

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        View::composer(['layouts.auth', 'layouts.main', 'partials.sidebar'], function ($view): void {
            $pengaturan = Schema::hasTable('pengaturans')
                ? Pengaturan::first()
                : null;

            $view->with('pengaturan', $pengaturan);
        });
    }
}