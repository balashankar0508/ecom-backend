<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'brand_id',   // ensure brand_id is fillable if you mass-assign it
        'title',
        'slug',
        'description',
        'status',
        'base_price',
        'tax_rate',
        'seo',
    ];

    protected $casts = [
        'seo' => 'array',
    ];

    // Existing relationship to Category (example)
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // <-- Add this brand relation
    public function brand()
    {
        // Adjust App\Models\Brand if your Brand model uses a different namespace
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    // other relations...
    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
}
?>