<?php

namespace Atifullahmamond\FilamentMeet\Console;

use Illuminate\Console\Command;

class FilamentMeetInstallCommand extends Command
{
    protected $signature = 'filament-meet:install
                            {--config : Publish only config/filament-meet.php}
                            {--migrations : Publish only database migrations}
                            {--force : Overwrite any existing published files}';

    protected $description = 'Publish filament-meet config and migrations';

    public function handle(): int
    {
        $configOpt = $this->option('config');
        $migrationsOpt = $this->option('migrations');

        if ($configOpt || $migrationsOpt) {
            $publishConfig = $configOpt;
            $publishMigrations = $migrationsOpt;
        } else {
            $publishConfig = true;
            $publishMigrations = true;
        }

        $force = (bool) $this->option('force');

        if ($publishConfig) {
            $this->components->info('Publishing config…');
            $this->call('vendor:publish', [
                '--tag' => 'filament-meet-config',
                '--force' => $force,
            ]);
        }

        if ($publishMigrations) {
            $this->components->info('Publishing migrations…');
            $this->call('vendor:publish', [
                '--tag' => 'filament-meet-migrations',
                '--force' => $force,
            ]);
        }

        $this->newLine();
        $this->components->info('Next steps');
        $this->line('  1. <fg=cyan>php artisan migrate</>');
        $this->line('  2. Add the <fg=cyan>meeting.{uuid}</> broadcast channel to <fg=cyan>routes/channels.php</> (see README).');
        $this->line('  3. Set <fg=cyan>BROADCAST_CONNECTION</> and Echo (Reverb/Pusher, etc.).');
        $this->newLine();

        return self::SUCCESS;
    }
}
