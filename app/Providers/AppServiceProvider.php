<?php

namespace App\Providers;

use App\Models\CrmContact;
use App\Models\CrmLead;
use App\Policies\CrmContactPolicy;
use App\Policies\CrmLeadPolicy;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(CrmLead::class, CrmLeadPolicy::class);
        Gate::policy(CrmContact::class, CrmContactPolicy::class);
    }
}
