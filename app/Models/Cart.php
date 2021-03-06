<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDealerDiscountPrice(): float
    {
        return $this->user->role?->name === "dealer"
            ? ((float)setting("cart.dealer_discount") * $this->subtotal) / 100
            : 0;
    }

    public function getTotalWithoutTax(float|string $delivery_price): float
    {
        return ($this->subtotal + (float) $delivery_price) - $this->discount_price - $this->getDealerDiscountPrice();
    }

    public function getTaxPrice(float|string $delivery_price = 0): float
    {
        return ((float)setting("cart.tax") * $this->getTotalWithoutTax($delivery_price)) / 100;
    }

    public function getTotal(float|string $delivery_price = 0): float
    {
        return $this->getTotalWithoutTax($delivery_price) + $this->getTaxPrice($delivery_price);
    }
}
