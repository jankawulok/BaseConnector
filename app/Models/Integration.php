<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Integration extends Model
{
    use CrudTrait;
    use HasFactory;
    protected $fillable = [
        'name',
        'enabled',
        'api_key',
        'full_feed_url',
        'light_feed_url',
        'full_import_definition',  // JSON for full import paths
        'light_import_definition',  // JSON for light import paths
        'full_sync_schedule',
        'light_sync_schedule',
        'last_full_sync',
        'last_light_sync',
    ];

    protected $casts = [
        'enabled' => 'boolean',
        'full_import_definition' => 'array',
        'light_import_definition' => 'array',
        'last_full_sync' => 'datetime',
        'last_light_sync' => 'datetime',
    ];

    // One integration has many products
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // One integration has many categories
    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    // Add this to the existing Integration model
    public function logs()
    {
        return $this->hasMany(Log::class);
    }

    public function alerts()
    {
        return $this->hasMany(Alert::class);
    }

    public function isFullSyncDue(): bool
    {
        if (!$this->enabled || empty($this->full_sync_schedule)) {
            return false;
        }

        return $this->isScheduleDue($this->full_sync_schedule, $this->last_full_sync);
    }

    public function isLightSyncDue(): bool
    {
        if (!$this->enabled || empty($this->light_sync_schedule)) {
            return false;
        }

        return $this->isScheduleDue($this->light_sync_schedule, $this->last_light_sync);
    }

    private function isScheduleDue(string $schedule, ?\DateTime $lastRun): bool
    {

        try {
            $cron = new \Cron\CronExpression($schedule);
            if ($lastRun === null) {
                return true;
            }
            return $cron->isDue($lastRun);
        } catch (\Exception $e) {
            \Log::error('Invalid cron expression: ' . $e->getMessage());
            return false;
        }
    }
}
