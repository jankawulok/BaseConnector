<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Product extends Model
{
    use CrudTrait, HasFactory;

    public $incrementing = true; // Use auto-incrementing primary key
    protected $primaryKey = 'auto_id'; // Single primary key field

    protected $fillable = [
        'id', 'integration_id', 'sku', 'ean', 'name', 'quantity', 'price', 'currency',
        'tax', 'weight', 'height', 'length', 'width', 'description', 'description_extra1',
        'description_extra2', 'description_extra3', 'description_extra4', 'man_name',
        'location', 'url', 'images', 'features', 'delivery_time', 'variants', 'deleted'
    ];

    protected $casts = [
        'id' => 'string',
        'images' => 'array',
        'features' => 'array',
        'variants' => 'array',
        'price' => 'float',
        'quantity' => 'integer',
        'tax' => 'integer',
        'weight' => 'float',
        'height' => 'float',
        'length' => 'float',
        'width' => 'float',
        'delivery_time' => 'integer',
        'deleted' => 'boolean',
    ];

    protected $attributes = [
        'sku' => '',
        'ean' => '',
        'name' => '',
        'quantity' => 0,
        'price' => 0.0,
        'currency' => 'USD',
        'tax' => 0,
        'weight' => 0.0,
        'height' => 0.0,
        'length' => 0.0,
        'width' => 0.0,
        'description' => '',
        'description_extra1' => '',
        'description_extra2' => '',
        'description_extra3' => '',
        'description_extra4' => '',
        'man_name' => '',
        'location' => '',
        'url' => '',
        'images' => '[]',
        'features' => '[]',
        'delivery_time' => 1,
        'variants' => '[]',
        'deleted' => false,
    ];

    // Many-to-Many relationship with Category
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_auto_id', 'category_auto_id');
    }

    // Relationship with Integration
    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
}
