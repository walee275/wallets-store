<?php

namespace App\Models;

use Database\Factories\ProductOptionValueFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductOptionValue extends Model
{
    /** @use HasFactory<ProductOptionValueFactory> */
    use HasFactory;

    protected $fillable = [
        'option_type_id',
        'value',
        'position',
    ];

    public function optionType(): BelongsTo
    {
        return $this->belongsTo(ProductOptionType::class, 'option_type_id');
    }
}
