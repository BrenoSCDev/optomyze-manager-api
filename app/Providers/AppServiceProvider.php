<?php

namespace App\Providers;

use App\Models\DocFolder;
use App\Models\DocPage;
use App\Models\DriveFile;
use App\Models\DriveFolder;
use App\Policies\DocFolderPolicy;
use App\Policies\DocPagePolicy;
use App\Policies\DriveFilePolicy;
use App\Policies\DriveFolderPolicy;
use App\Services\CrmCompanyService;
use App\Services\DocService;
use App\Services\DriveService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(CrmCompanyService::class);
        $this->app->singleton(DocService::class);
        $this->app->singleton(DriveService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(DocFolder::class, DocFolderPolicy::class);
        Gate::policy(DocPage::class, DocPagePolicy::class);
        Gate::policy(DriveFolder::class, DriveFolderPolicy::class);
        Gate::policy(DriveFile::class, DriveFilePolicy::class);
    }
}
