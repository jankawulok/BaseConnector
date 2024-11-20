<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class ProductHistory extends Model
{
    use CrudTrait;

    protected $fillable = [
        'product_auto_id',
        'field_name',
        'old_value',
        'new_value',
        'variant_id',
    ];

    protected $casts = [
        'old_value' => 'float',
        'new_value' => 'float',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_auto_id', 'auto_id');
    }

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    public function getFieldNameAttribute($value)
    {
        return ucfirst($value);
    }

    public function getOldValueAttribute($value)
    {
        return $this->formatValue($value, $this->field_name);
    }

    public function getNewValueAttribute($value)
    {
        return $this->formatValue($value, $this->field_name);
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    public function scopePrice($query)
    {
        return $query->where('field_name', 'price');
    }

    public function scopeQuantity($query)
    {
        return $query->where('field_name', 'quantity');
    }

    private function formatValue($value, $fieldName)
    {
        switch (strtolower($fieldName)) {
            case 'price':
                return number_format((float)$value, 2);
            case 'quantity':
                return (int)$value;
            default:
                return $value;
        }
    }
}
