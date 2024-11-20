<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Jobs\ImportIntegrationFeed;
use Illuminate\Console\Command;

class RunIntegrationSyncs extends Command
{
    protected $signature = 'integrations:sync';
    protected $description = 'Run scheduled integration syncs';

    public function handle()
    {
        $integrations = Integration::where('enabled', true)->get();

        foreach ($integrations as $integration) {
            if ($integration->shouldRunFullSync()) {
                ImportIntegrationFeed::dispatch($integration, 'full');
                $integration->update(['last_full_sync' => now()]);
                $this->info("Queued full sync for integration: {$integration->name}");
            }

            if ($integration->shouldRunLightSync()) {
                ImportIntegrationFeed::dispatch($integration, 'light');
                $integration->update(['last_light_sync' => now()]);
                $this->info("Queued light sync for integration: {$integration->name}");
            }
        }
    }
}
