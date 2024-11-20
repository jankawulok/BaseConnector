<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Alert extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'integration_id',
        'type',
        'condition',
        'filters',
        'is_active',
        'notification_email',
        'last_notification_at'
    ];

    protected $casts = [
        'condition' => 'array',
        'filters' => 'array',
        'is_active' => 'boolean',
        'last_notification_at' => 'datetime'
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }

    public function matchesFilters(Product $product): bool
    {
        if (empty($this->filters)) return true;

        foreach ($this->filters as $field => $value) {
            switch ($field) {
                case 'sku_pattern':
                    if (!preg_match($value, $product->sku)) return false;
                    break;
                case 'min_price':
                    if ($product->price < $value) return false;
                    break;
                case 'max_price':
                    if ($product->price > $value) return false;
                    break;
                case 'category':
                    if (!$product->categories->contains('id', $value)) return false;
                    break;
            }
        }
        return true;
    }
}
