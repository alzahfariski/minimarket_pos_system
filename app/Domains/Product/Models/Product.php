<?php

namespace App\Domains\Product\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image_path 
                ? \Illuminate\Support\Facades\Storage::disk('s3')->url($this->image_path)
                : null,
        );
    }

    protected $fillable = [
        'sku',
        'name',
        'cost',
        'price',
        'stock', // Guarded logic handled in methods, but fillable for initial create
        'image_path',
    ];

    protected $casts = [
        'cost' => 'integer',
        'price' => 'integer',
        'stock' => 'integer',
    ];

    /** 
     * Stock is guarded and should only be modified via domain actions 
     */
    protected $guarded = ['stock'];

    public function increaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be greater than zero.');
        }

        $this->increment('stock', $quantity);
    }

    public function decreaseStock(int $quantity): void
    {
        if ($quantity <= 0) {
            throw new \DomainException('Quantity must be greater than zero.');
        }

        if ($this->stock < $quantity) {
             throw new \DomainException("Insufficient stock for product {$this->name}. Available: {$this->stock}, Requested: {$quantity}");
        }

        $this->decrement('stock', $quantity);
    }
}
