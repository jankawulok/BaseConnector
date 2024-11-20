<?php

namespace App\Observers;

use App\Models\ProductHistory;
use App\Models\Alert;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class ProductHistoryObserver
{
    public function created(ProductHistory $history)
    {
        $product = $history->product;
        if (!$product) return;

        $alerts = Alert::where('integration_id', $product->integration_id)
            ->where('is_active', true)
            ->get();

        foreach ($alerts as $alert) {
            if ($alert->checkCondition($history)) {
                $this->sendAlert($alert, $history);
            }
        }
    }

    private function sendAlert($alert, $history)
    {
        $data = [
            'product' => $history->product,
            'field' => $history->field_name,
            'old_value' => $history->old_value,
            'new_value' => $history->new_value,
            'variant_id' => $history->variant_id,
            'change_time' => $history->created_at
        ];

        Mail::send('emails.product_alert', $data, function($message) use ($alert) {
            $message->to($alert->notification_email)
                ->subject('Product Alert: ' . ucfirst($alert->type));
        });

        Log::info('Alert sent', [
            'alert_id' => $alert->id,
            'type' => $alert->type,
            'history_id' => $history->id,
            'product_id' => $history->product_auto_id
        ]);
    }
}
