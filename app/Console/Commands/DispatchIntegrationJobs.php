<?php

namespace App\Console\Commands;

use App\Models\Integration;
use App\Jobs\ImportIntegrationFeed;
use Illuminate\Console\Command;

class DispatchIntegrationJobs extends Command
{
    protected $signature = 'integrations:dispatch';
    protected $description = 'Check and dispatch integration jobs based on their schedules';

    public function handle()
    {
        $integrations = Integration::where('enabled', true)->get();

        foreach ($integrations as $integration) {
            var_dump($integration->name);
            // Check full sync
            if ($integration->isFullSyncDue()) {
                ImportIntegrationFeed::dispatch($integration, 'full');
                $integration->last_full_sync = now();
                $integration->save();
            }

            // Check light sync
            elseif ($integration->isLightSyncDue()) {
                ImportIntegrationFeed::dispatch($integration, 'light');
                $integration->last_light_sync = now();
                $integration->save();
            }
        }
    }
}
