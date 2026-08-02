<?php

namespace App\Providers;

use App\Helpers\ViewConfigHelper;
use App\Interfaces\Repositories\AbsensiRepositoryInterface;
use App\Interfaces\Repositories\DeliveryOrderRepositoryInterface;
use App\Interfaces\Repositories\DivisiRepositoryInterface;
use App\Interfaces\Repositories\InformasiRepositoryInterface;
use App\Interfaces\Repositories\InvoiceRepositoryInterface;
use App\Interfaces\Repositories\IzinRepositoryInterface;
use App\Interfaces\Repositories\JenisIzinRepositoryInterface;
use App\Interfaces\Repositories\KantorRepositoryInterface;
use App\Interfaces\Repositories\KunjunganRepositoryInterface;
use App\Interfaces\Repositories\MenuRepositoryInterface;
use App\Interfaces\Repositories\MitraCategoryRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\AkuntansiAccountRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\AkuntansiJournalEntryRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\MitraMaterialRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\MitraProductRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\MitraStockMovementRepositoryInterface;
use App\Interfaces\Repositories\MitraPos\PosTransactionRepositoryInterface;
use App\Interfaces\Repositories\MitraRepositoryInterface;
use App\Interfaces\Repositories\PegawaiRepositoryInterface;
use App\Interfaces\Repositories\ProductCategoryRepositoryInterface;
use App\Interfaces\Repositories\ProductsRepositoryInterface;
use App\Interfaces\Repositories\ProductSubCategoryRepositoryInterface;
use App\Interfaces\Repositories\ProduksiRepositoryInterface;
use App\Interfaces\Repositories\RoleRepositoryInterface;
use App\Interfaces\Repositories\SalesOrderLogRepositoryInterface;
use App\Interfaces\Repositories\SalesOrderRepositoryInterface;
use App\Interfaces\Repositories\ShiftRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Listeners\BackupNotificationListener;
use App\Models\Role;
use App\Repositories\AbsensiRepository;
use App\Repositories\DeliveryOrderRepository;
use App\Repositories\DivisiRepository;
use App\Repositories\InformasiRepository;
use App\Repositories\InvoiceRepository;
use App\Repositories\IzinRepository;
use App\Repositories\JenisIzinRepository;
use App\Repositories\KantorRepository;
use App\Repositories\KunjunganRepository;
use App\Repositories\MenuRepository;
use App\Repositories\MitraCategoryRepository;
use App\Repositories\MitraPos\AkuntansiAccountRepository;
use App\Repositories\MitraPos\AkuntansiJournalEntryRepository;
use App\Repositories\MitraPos\MitraMaterialRepository;
use App\Repositories\MitraPos\MitraProductRepository;
use App\Repositories\MitraPos\MitraStockMovementRepository;
use App\Repositories\MitraPos\PosTransactionRepository;
use App\Repositories\MitraRepository;
use App\Repositories\PegawaiRepository;
use App\Repositories\ProductCategoryRepository;
use App\Repositories\ProductsRepository;
use App\Repositories\ProductSubCategoryRepository;
use App\Repositories\ProduksiRepository;
use App\Repositories\RoleRepository;
use App\Repositories\SalesOrderLogRepository;
use App\Repositories\SalesOrderRepository;
use App\Repositories\ShiftRepository;
use App\Repositories\UserRepository;
use App\Services\MitraPos\MitraContext;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Masbug\Flysystem\GoogleDriveAdapter;

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
            RoleRepositoryInterface::class,
            RoleRepository::class
        );
        $this->app->bind(
            MenuRepositoryInterface::class,
            MenuRepository::class
        );
        $this->app->bind(
            ProductsRepositoryInterface::class,
            ProductsRepository::class
        );

        // Absensi System Repositories
        $this->app->bind(
            DivisiRepositoryInterface::class,
            DivisiRepository::class
        );
        $this->app->bind(
            KantorRepositoryInterface::class,
            KantorRepository::class
        );
        $this->app->bind(
            JenisIzinRepositoryInterface::class,
            JenisIzinRepository::class
        );
        $this->app->bind(
            PegawaiRepositoryInterface::class,
            PegawaiRepository::class
        );
        $this->app->bind(
            AbsensiRepositoryInterface::class,
            AbsensiRepository::class
        );
        $this->app->bind(
            IzinRepositoryInterface::class,
            IzinRepository::class
        );
        $this->app->bind(
            ShiftRepositoryInterface::class,
            ShiftRepository::class
        );
        $this->app->bind(
            InformasiRepositoryInterface::class,
            InformasiRepository::class
        );

        // Product & Mitra Module Repositories
        $this->app->bind(
            ProductCategoryRepositoryInterface::class,
            ProductCategoryRepository::class
        );
        $this->app->bind(
            ProductSubCategoryRepositoryInterface::class,
            ProductSubCategoryRepository::class
        );
        $this->app->bind(
            MitraCategoryRepositoryInterface::class,
            MitraCategoryRepository::class
        );
        $this->app->bind(
            MitraRepositoryInterface::class,
            MitraRepository::class
        );

        // Kunjungan Module Repository
        $this->app->bind(
            KunjunganRepositoryInterface::class,
            KunjunganRepository::class
        );

        // Produksi Module Repository
        $this->app->bind(
            ProduksiRepositoryInterface::class,
            ProduksiRepository::class
        );

        // Sales Order Module Repositories
        $this->app->bind(
            SalesOrderRepositoryInterface::class,
            SalesOrderRepository::class
        );
        $this->app->bind(
            DeliveryOrderRepositoryInterface::class,
            DeliveryOrderRepository::class
        );
        $this->app->bind(
            InvoiceRepositoryInterface::class,
            InvoiceRepository::class
        );
        $this->app->bind(
            SalesOrderLogRepositoryInterface::class,
            SalesOrderLogRepository::class
        );

        // Mitra POS Module Repositories
        $this->app->bind(
            MitraMaterialRepositoryInterface::class,
            MitraMaterialRepository::class
        );
        $this->app->bind(
            MitraStockMovementRepositoryInterface::class,
            MitraStockMovementRepository::class
        );
        $this->app->bind(
            MitraProductRepositoryInterface::class,
            MitraProductRepository::class
        );
        $this->app->bind(
            PosTransactionRepositoryInterface::class,
            PosTransactionRepository::class
        );
        $this->app->bind(
            AkuntansiAccountRepositoryInterface::class,
            AkuntansiAccountRepository::class
        );
        $this->app->bind(
            AkuntansiJournalEntryRepositoryInterface::class,
            AkuntansiJournalEntryRepository::class
        );
        $this->app->scoped(MitraContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Define Gates for authorization
        Gate::before(function ($user, $ability) {
            if ($user->role && $user->role->slug === 'super-admin') {
                return true;
            }
        });

        // Register Backup Notification Subscriber
        Event::subscribe(BackupNotificationListener::class);

        Gate::define('access', function ($user, $slug, $action) {
            return $user->hasPermission($slug, $action);
        });
        // Create Helper alias for ViewConfigHelper
        if (! class_exists('Helper')) {
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
                $role = Role::where('slug', 'super-admin')->first();
            }

            if ($role) {
                $menus = $role->menus()
                    ->whereNull('parent_id')
                    ->where('is_active', true)
                    ->wherePivot('can_read', true)
                    ->with(['children' => function ($q) use ($role) {
                        $q->where('is_active', true)->whereHas('roles', function ($rq) use ($role) {
                            $rq->where('roles.id', $role->id)->where('can_read', true);
                        })->with(['children' => function ($sq) use ($role) {
                            $sq->where('is_active', true)->whereHas('roles', function ($srq) use ($role) {
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

        // Register Google Drive Storage Driver
        try {
            Storage::extend('google', function ($app, $config) {
                $client = new Client;
                $client->setClientId($config['clientId']);
                $client->setClientSecret($config['clientSecret']);
                $client->refreshToken($config['refreshToken']);

                $service = new Drive($client);
                $adapter = new GoogleDriveAdapter($service, $config['folderId'] ?? '/', []);
                $driver = new Filesystem($adapter);

                return new FilesystemAdapter($driver, $adapter);
            });
        } catch (\Exception $e) {
            // Silently fail if dependencies are missing
        }
    }
}
