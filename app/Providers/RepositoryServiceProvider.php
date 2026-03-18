<?php

namespace App\Providers;

use App\Interfaces\AuthRepositoryInterface;
use App\Interfaces\MasterDataRepositoryInterface;
use App\Interfaces\ProfileRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Repositories\AuthRepository;
use App\Repositories\BrandRepository;
use App\Repositories\CategoryRepository;
use App\Repositories\GradeRepository;
use App\Repositories\ProfileRepository;
use App\Repositories\SalesRepRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\TaxRepository;
use App\Repositories\UnitRepository;
use App\Repositories\UserRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Core Repositories
        $this->app->bind(AuthRepositoryInterface::class, AuthRepository::class);
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(ProfileRepositoryInterface::class, ProfileRepository::class);

        // Master Data Repositories (bound directly, no interface needed since controllers inject concrete classes)
        // The MasterDataRepositoryInterface is used as a contract reference but
        // each controller injects the concrete repository directly for simplicity.
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
