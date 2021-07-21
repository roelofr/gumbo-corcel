<?php

declare(strict_types=1);

namespace App\Models\Shop;

use App\Models\Traits\IsSluggable;
use App\Models\Traits\IsUuidModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single product.
 *
 * @property string $id
 * @property null|string $category_id
 * @property \Illuminate\Support\Date $created_at
 * @property \Illuminate\Support\Date $updated_at
 * @property string $name
 * @property null|string $description
 * @property string $slug
 * @property null|string $image_url
 * @property null|string $etag
 * @property int $vat_rate
 * @property bool $visible
 * @property bool $advertise_on_home
 * @property array $meta
 * @property array $images A list of strings for image paths.
 * @property-read string $valid_image_url
 * @property-read null|ProductVariant $default_variant
 * @property-read null|\App\Models\Shop\Category $category
 * @property-read \Illuminate\Database\Eloquent\Collection<ProductVariant> $variants
 */
class Product extends Model
{
    use IsSluggable;
    use IsUuidModel;

    protected $table = 'shop_products';

    protected $casts = [
        'visible' => 'bool',
        'advertise_on_home' => 'bool',
        'vat_rate' => 'int',
        'meta' => 'json',
        'images' => 'array',
    ];

    protected $attributes = [
        'meta' => '[]',
    ];

    /**
     * {@inheritdoc}
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'unique' => true,
                'maxLengthKeepWords' => 48,
                'source' => 'name',
            ],
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)
            ->orderBy('order')
            ->orderBy('name');
    }

    public function getValidImageUrlAttribute(): string
    {
        return $this->image_url ?? (string) mix('images/geen-foto.jpg');
    }

    public function getDefaultVariantAttribute(): ?ProductVariant
    {
        return $this->variants
            ->first();
    }
}
