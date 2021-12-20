<?php

namespace App\Models;

use App\Models\AttributeProduct;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $casts = [
        'allowed_quantities' => 'array',
    ];

    public function attributes()
    {
        return $this->belongsToMany(Attribute::class)->withPivot(['options'])->using(AttributeProduct::class);
    }
}
