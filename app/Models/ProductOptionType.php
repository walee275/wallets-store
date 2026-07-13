<?php

namespace App\Models;

use Database\Factories\ProductOptionTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductOptionType extends Model
{
    /** @use HasFactory<ProductOptionTypeFactory> */
    use HasFactory;

    protected $fillable = [
        'product_id',
        'name',
        'position',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function values(): HasMany
    {
        return $this->hasMany(ProductOptionValue::class, 'option_type_id');
    }
}
