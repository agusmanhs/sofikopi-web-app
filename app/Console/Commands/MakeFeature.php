<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeFeature extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:feature {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD structure (Controller, Service, Repository, Request)';

    protected Filesystem $files;
    protected string $feature;


    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->feature = Str::studly($this->argument('name'));

        $this->makeDirectories();
        $this->makeRepository();
        $this->makeService();
        $this->makeController();
        $this->makeRequest();

        $this->newLine();
        $this->info("✅ CRUD {$this->feature} generated successfully!");
        $this->showBindingHint();
    }

    /**
     * ===============================
     * DIRECTORIES
     * ===============================
     */
    protected function makeDirectories()
    {
        $dirs = [
            app_path('Repositories'),
            app_path('Services'),
            app_path('Interfaces/Repositories'),
            app_path('Http/Controllers'),
            app_path('Http/Requests'),
        ];

        foreach ($dirs as $dir) {
            if (! $this->files->exists($dir)) {
                $this->files->makeDirectory($dir, 0755, true);
            }
        }
    }

    /**
     * ===============================
     * REPOSITORY + INTERFACE
     * ===============================
     */
    protected function makeRepository(): void
    {
        $repositoryPath = app_path("Repositories/{$this->feature}Repository.php");
        $interfacePath  = app_path("Interfaces/Repositories/{$this->feature}RepositoryInterface.php");

        if (! $this->files->exists($interfacePath)) {
            $this->files->put($interfacePath, <<<PHP
<?php

namespace App\Interfaces\Repositories;

interface {$this->feature}RepositoryInterface
{
}
PHP);
        }

        if (! $this->files->exists($repositoryPath)) {
            $this->files->put($repositoryPath, <<<PHP
<?php

namespace App\Repositories;

use App\Models\\{$this->feature};
use App\Interfaces\Repositories\\{$this->feature}RepositoryInterface;

class {$this->feature}Repository extends BaseRepository implements {$this->feature}RepositoryInterface
{
    public function __construct({$this->feature} \$model)
    {
        \$this->model = \$model;
    }
}
PHP);
        }
    }

    /**
     * ===============================
     * SERVICE
     * ===============================
     */
    protected function makeService(): void
    {
        $servicePath = app_path("Services/{$this->feature}Service.php");

        if ($this->files->exists($servicePath)) return;

        $this->files->put($servicePath, <<<PHP
<?php

namespace App\Services;

use App\Repositories\\{$this->feature}Repository;

class {$this->feature}Service extends BaseService
{
    public function __construct({$this->feature}Repository \$repository)
    {
        parent::__construct(\$repository);
    }
}
PHP);
    }

    /**
     * ===============================
     * REQUEST
     * ===============================
     */
    protected function makeRequest(): void
    {
        $requestPath = app_path("Http/Requests/{$this->feature}Request.php");

        if ($this->files->exists($requestPath)) return;

        $this->files->put($requestPath, <<<PHP
<?php

namespace App\Http\Requests;

class {$this->feature}Request extends BaseRequest
{
    public function rules(): array
    {
        return [
            // 'name' => 'required|string|max:255',
        ];
    }
}
PHP);
    }

    /**
     * ===============================
     * CONTROLLER
     * ===============================
     */
    protected function makeController(): void
    {
        $controllerPath = app_path("Http/Controllers/{$this->feature}Controller.php");
        $slug = Str::kebab($this->feature);
        $viewPath = Str::snake($this->feature, '-');

        if ($this->files->exists($controllerPath)) return;

        $this->files->put($controllerPath, <<<PHP
<?php

namespace App\Http\Controllers;

use App\Services\\{$this->feature}Service;
use App\Http\Requests\\{$this->feature}Request;
use Illuminate\Http\Request;

class {$this->feature}Controller extends Controller
{
    public function __construct(
        protected {$this->feature}Service \$service
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        \$data = \$this->service->all();
        return view('pages.{$viewPath}.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('pages.{$viewPath}.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store({$this->feature}Request \$request)
    {
        \$data = \$request->validated();
        \$this->service->create(\$data);

        return redirect()->route('{$slug}.index')
            ->with('success', 'Data berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(\$id)
    {
        \$data = \$this->service->find(\$id);
        return view('pages.{$viewPath}.show', compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\$id)
    {
        \$data = \$this->service->find(\$id);
        return view('pages.{$viewPath}.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update({$this->feature}Request \$request, \$id)
    {
        \$data = \$request->validated();
        \$this->service->update(\$id, \$data);

        return redirect()->route('{$slug}.index')
            ->with('success', 'Data berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\$id)
    {
        \$this->service->delete(\$id);

        return redirect()->route('{$slug}.index')
            ->with('success', 'Data berhasil dihapus!');
    }
}
PHP);

        $this->info("💡 Jangan lupa tambahkan route di routes/web.php:");
        $this->line("   Route::resource('{$slug}', \\App\\Http\\Controllers\\{$this->feature}Controller::class);");
    }

    /**
     * ===============================
     * AUTO BINDING HINT
     * ===============================
     */
    protected function showBindingHint(): void
    {
        $this->newLine();
        $this->info("📌 Tambahkan binding di AppServiceProvider::register():");
        $this->line("   \$this->app->bind(");
        $this->line("       \\App\\Interfaces\\Repositories\\{$this->feature}RepositoryInterface::class,");
        $this->line("       \\App\\Repositories\\{$this->feature}Repository::class");
        $this->line("   );");
    }
}
