<?php

namespace App\Console\Commands;

use App\Models\Alert;
use App\Models\ProductHistory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

class CheckProductAlerts extends Command
{
    protected $signature = 'alerts:check';
    protected $description = 'Check product alerts and send notifications';

    public function handle()
    {
        $alerts = Alert::where('is_active', true)->get();

        foreach ($alerts as $alert) {
            $changes = $this->getRelevantChanges($alert);
            if ($changes->isNotEmpty()) {
                $this->sendAlertNotification($alert, $changes);
            }
        }
    }

    private function getRelevantChanges($alert)
    {
        $query = ProductHistory::with('product')
            ->where('created_at', '>', $alert->last_notification_at ?? now()->subDay())
            ->whereHas('product', function($q) use ($alert) {
                $q->where('integration_id', $alert->integration_id);
            });

        if ($alert->type === 'price_change') {
            $query->where('field_name', 'price');
        } elseif ($alert->type === 'stock_change') {
            $query->where('field_name', 'quantity');
        }

        return $query->get()->filter(function($history) use ($alert) {
            if (!$alert->matchesFilters($history->product)) return false;

            switch ($alert->type) {
                case 'price_change':
                    $oldPrice = (float)$history->old_value;
                    $newPrice = (float)$history->new_value;
                    $percentChange = abs(($newPrice - $oldPrice) / $oldPrice * 100);
                    return $percentChange >= $alert->condition['percentage'];

                case 'stock_change':
                    $change = abs((int)$history->new_value - (int)$history->old_value);
                    return $change >= $alert->condition['threshold'];
            }
            return false;
        });
    }

    private function sendAlertNotification($alert, $changes)
    {
        Mail::send('emails.product_alerts_digest', [
            'alert' => $alert,
            'changes' => $changes,
            'integration' => $alert->integration
        ], function($message) use ($alert) {
            $message->to($alert->notification_email)
                ->subject('Product Alert Digest: ' . $alert->integration->name);
        });

        $alert->update(['last_notification_at' => now()]);
    }
}
