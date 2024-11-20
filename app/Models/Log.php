<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
class Log extends Model
{
    use HasFactory;
    use CrudTrait;

    protected $fillable = [
        'integration_id',
        'level',
        'message',
        'context'
    ];

    protected $casts = [
        'context' => 'array'
    ];

    public function integration()
    {
        return $this->belongsTo(Integration::class);
    }
}
