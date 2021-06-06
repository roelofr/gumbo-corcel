<?php

declare(strict_types=1);

namespace App\Nova\Resources\Shop;

use App\Models\Shop\Order as Model;
use App\Nova\Fields\Price;
use App\Nova\Resources\Resource;
use App\Nova\Resources\User;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\Badge;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\BelongsToMany;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;

// phpcs:disable SlevomatCodingStandard.Functions.UnusedParameter.UnusedParameter
class Order extends Resource
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
     * Indicates if the resource should be globally searchable.
     *
     * @var bool
     */
    public static $globallySearchable = false;

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'id',
    ];

    /**
     * Get the fields displayed by the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function fields(Request $request)
    {
        return [
            ID::make(),

            DateTime::make(__('Paid at'), 'paid_at')
                ->onlyOnDetail(),

            DateTime::make(__('Shipped at'), 'shipped_at')
                ->onlyOnDetail(),

            BelongsTo::make(__('User'), 'user', User::class)
                ->exceptOnForms(),

            Badge::make(__('Status'), 'status')
                ->onlyOnIndex()
                ->displayUsing(static fn ($status) => __(ucfirst($status)))
                ->map([
                    __('Sent') => 'success',
                    __('Paid') => 'info',
                    __('Pending') => 'warning',
                ]),

            Price::make(__('Price'), 'price')
                ->sortable()
                ->min(1.00),

            BelongsToMany::make(__('Products'), 'products', ProductVariant::class)
                ->fields(new OrderProductFields()),
        ];
    }
}
