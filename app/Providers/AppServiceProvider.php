<?php

namespace App\Providers;

use App\Helpers\ViewConfigHelper;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Repository bindings
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(
            \App\Interfaces\Repositories\RoleRepositoryInterface::class,
            \App\Repositories\RoleRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\MenuRepositoryInterface::class,
            \App\Repositories\MenuRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\ProductsRepositoryInterface::class,
            \App\Repositories\ProductsRepository::class
        );

        // Absensi System Repositories
        $this->app->bind(
            \App\Interfaces\Repositories\DivisiRepositoryInterface::class,
            \App\Repositories\DivisiRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\KantorRepositoryInterface::class,
            \App\Repositories\KantorRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\JenisIzinRepositoryInterface::class,
            \App\Repositories\JenisIzinRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\PegawaiRepositoryInterface::class,
            \App\Repositories\PegawaiRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\AbsensiRepositoryInterface::class,
            \App\Repositories\AbsensiRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\IzinRepositoryInterface::class,
            \App\Repositories\IzinRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\ShiftRepositoryInterface::class,
            \App\Repositories\ShiftRepository::class
        );
        $this->app->bind(
            \App\Interfaces\Repositories\InformasiRepositoryInterface::class,
            \App\Repositories\InformasiRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Define Gates for authorization
        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->role && $user->role->slug === 'super-admin') {
                return true;
            }
        });

        \Illuminate\Support\Facades\Gate::define('access', function ($user, $slug, $action) {
            return $user->hasPermission($slug, $action);
        });
        // Create Helper alias for ViewConfigHelper
        if (!class_exists('Helper')) {
            class_alias(ViewConfigHelper::class, 'Helper');
        }

        // Share menu data with all views
        View::composer('*', function ($view) {
            $menus = collect();
            
            if (auth()->check()) {
                $role = auth()->user()->role;
            } else {
                // Fallback to Super Admin menus for Guest/Demo if no auth
                // Or just the first role found
                $role = \App\Models\Role::where('slug', 'super-admin')->first();
            }

            if ($role) {
                $menus = $role->menus()
                    ->whereNull('parent_id')
                    ->wherePivot('can_read', true)
                    ->with(['children' => function($q) use ($role) {
                        $q->whereHas('roles', function($rq) use ($role) {
                            $rq->where('roles.id', $role->id)->where('can_read', true);
                        })->with(['children' => function($sq) use ($role) {
                            $sq->whereHas('roles', function($srq) use ($role) {
                                $srq->where('roles.id', $role->id)->where('can_read', true);
                            })->orderBy('order_no');
                        }])->orderBy('order_no');
                    }])
                    ->orderBy('order_no')
                    ->get();
            }

            // Fallback to JSON if no menus found in DB
            if ($menus->isEmpty()) {
                $verticalMenuJson = file_get_contents(resource_path('menu/verticalMenu.json'));
                $menus = json_decode($verticalMenuJson)->menu ?? [];
            }

            $view->with('menuData', [$menus]);
            $view->with('menuHorizontal', [[]]); // Placeholder
        });

        // Share template variables config
        config([
            'variables' => [
                'templateName' => 'SOFIKOPI',
                'templateVersion' => '1.0.0',
                'templateFree' => false,
                'templatePrefix' => '',
                'templateSuffix' => '',
                'templateDomain' => 'localhost',
                'templateAuthor' => 'CV. Data Cipta Celebes',
                'templateAuthorUrl' => '#',
                'creatorName' => 'CV. Data Cipta Celebes',
                'creatorUrl' => '#',
                'documentation' => 'https://demos.pixinvent.com/materialize-html-admin-template/documentation/',
            ],
        ]);
    }
}
