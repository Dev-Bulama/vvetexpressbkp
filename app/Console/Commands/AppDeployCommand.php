<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Throwable;

class AppDeployCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:deploy {--seed : Force running the essential-data seeders even if they appear to have already run}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Safe, repeatable production deployment steps (migrations, storage, caches). Never destroys existing data.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        if (! File::exists(base_path('.env'))) {
            $this->error('.env not found. Create it manually on the server before running this command.');

            return self::FAILURE;
        }

        try {
            $this->info('Running database migrations...');
            Artisan::call('migrate', ['--force' => true], $this->output);

            $this->ensureStorageDirectories();

            $this->info('Linking storage...');
            Artisan::call('storage:link', ['--force' => true], $this->output);

            if ($this->option('seed') || ! $this->essentialDataAlreadySeeded()) {
                $this->info('Seeding essential system data...');
                Artisan::call('db:seed', ['--force' => true], $this->output);
            } else {
                $this->info('Essential data already present, skipping seeders (use --seed to force).');
            }

            $this->info('Rebuilding production caches...');
            Artisan::call('optimize:clear', [], $this->output);
            Artisan::call('config:cache', [], $this->output);
            Artisan::call('route:cache', [], $this->output);
            Artisan::call('view:cache', [], $this->output);
        } catch (Throwable $e) {
            $this->error('Deployment step failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info('Deployment steps completed successfully.');

        return self::SUCCESS;
    }

    private function ensureStorageDirectories(): void
    {
        foreach ([
            storage_path('framework/cache/data'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
            storage_path('framework/testing'),
            storage_path('logs'),
            storage_path('app/public'),
        ] as $directory) {
            if (! File::isDirectory($directory)) {
                File::makeDirectory($directory, 0775, true);
            }
        }
    }

    private function essentialDataAlreadySeeded(): bool
    {
        try {
            return Role::query()->exists();
        } catch (Throwable) {
            return false;
        }
    }
}
