<?php

declare(strict_types=1);

namespace App\Nova\Resources\Shop;

use App\Models\Shop\Product as Model;
use App\Nova\Resources\Resource;
use Benjaminhirsch\NovaSlugField\Slug;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Boolean;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Number;
use Laravel\Nova\Fields\Text;

class Product extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = Model::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'name';

    /**
     * The logical group associated with the resource.
     *
     * @var string
     */
    public static $group = 'Shop';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
        'name',
        'slug',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(),

            Text::make(__('Name'), 'name'),
            Slug::make(__('Slug'), 'slug')
                ->disableAutoUpdateWhenUpdating()
                ->showUrlPreview(url('/shop/<category>/'))
                ->hideFromIndex()
                ->nullable(),

            Text::make(__('Description'), 'description')
                ->hideFromIndex()
                ->nullable(),

            Text::make(__('Image URL'), 'image_url')
                ->hideFromIndex()
                ->nullable(),

            Text::make(__('Entity Tag'), 'etag')
                ->onlyOnDetail(),

            Number::make(__('VAT'), 'vat_rate')
                ->hideFromIndex()
                ->min(0)
                ->max(100),

            Boolean::make(__('Visible'), 'visible'),

            Boolean::make(__('Advertise on homepage and shop landing'), 'advertise_on_home')
                ->help(__('The category and the product need to be visible for the advertisement to show.')),

            BelongsTo::make(__('Category'), 'category', Category::class)
                ->searchable(),

            HasMany::make(__('Variants'), 'variants', ProductVariant::class),

            // Add support for multiple images for the product
            HasMany::make(__('Images'), 'images', ProductImage::class),
        ];
    }
}
