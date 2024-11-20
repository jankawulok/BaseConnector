<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Backpack\CRUD\app\Models\Traits\CrudTrait;

class Category extends Model
{
    use CrudTrait, HasFactory;

    public $incrementing = true; // Enable auto-incrementing for `auto_id`
    protected $primaryKey = 'auto_id'; // Single primary key

    protected $fillable = ['name', 'integration_id', 'id'];

    protected $casts = [
        'id' => 'string'
        ];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'category_product', 'category_auto_id', 'product_auto_id');
    }
}
