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
        $this->line('  2. Meeting room UI: in <fg=cyan>resources/css/app.css</> add Tailwind <fg=cyan>@source</> for this package’s Blade files (ZIP installs: <fg=cyan>.../filament-meet/filament-meet/resources/</>), then <fg=cyan>npm install</> and <fg=cyan>npm run build</> (see README “Frontend assets”).');
        $this->line('  3. Add the <fg=cyan>meeting.{uuid}</> broadcast channel to <fg=cyan>routes/channels.php</> (see README).');
        $this->line('  4. Set <fg=cyan>BROADCAST_CONNECTION</> and Echo (Reverb/Pusher, etc.) — or <fg=cyan>FILAMENT_MEET_BROADCASTING=false</> to skip.');
        $this->newLine();

        return self::SUCCESS;
    }
}
