<?php

declare(strict_types=1);

namespace App\Nova\Resources;

use Advoor\NovaEditorJs\NovaEditorJs;
use App\Models\NewsItem as NewsItemModel;
use Benjaminhirsch\NovaSlugField\Slug;
use Benjaminhirsch\NovaSlugField\TextWithSlug;
use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Image;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;

/**
 * News Items.
 */
class NewsItem extends Resource
{
    /**
     * The model the resource corresponds to.
     *
     * @var string
     */
    public static $model = NewsItemModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     *
     * @var string
     */
    public static $title = 'title';

    /**
     * Name of the group.
     *
     * @var string
     */
    public static $group = 'Content';

    /**
     * The columns that should be searched.
     *
     * @var array
     */
    public static $search = [
        'title',
        'slug',
    ];

    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    public function fields(Request $request)
    {
        $options = config('gumbo.news-categories', ['Nieuws']);

        return [
            ID::make()->sortable(),

            TextWithSlug::make('Titel', 'title')->slug('slug'),
            Slug::make('Pad', 'slug')->nullable(false),

            // Category
            Select::make('Categorie', 'category')
                ->required()
                ->options(\array_combine($options, $options)),

            // Add sponsor field
            Text::make('Headline', 'headline')
                ->nullable()
                ->help('De headline van dit artikel, net zoals in de krant.')
                ->hideFromIndex(),

            // Add multi selects
            BelongsTo::make('Laatst bewerkt door', 'author', User::class)
                ->onlyOnDetail(),

            // Add sponsor field
            Text::make('Sponsor', 'sponsor')
                ->nullable()
                ->help('De sponsor die voor deze post heeft betaald. Zet dit artikel om in een advertorial.')
                ->hideFromIndex(),

            // Show timestamps
            DateTime::make('Aangemaakt op', 'created_at')->onlyOnDetail(),
            DateTime::make('Laatst bewerkt op', 'created_at')->onlyOnDetail(),
            DateTime::make('Gepubliceerd op', 'published_at')
                ->nullable()
                ->help('Datum waarop dit artikel gepubliceerd is of wordt')
                ->hideFromIndex(),

            // Image
            Image::make('Afbeelding', 'cover_imge')
                ->deletable()
                ->nullable()
                ->help('Afbeelding die bij het artikel en op Social Media getoond wordt.')
                ->rules(
                    'nullable',
                    'image',
                ),

            // Add data
            NovaEditorJs::make('Inhoud', 'contents')->hideFromIndex()->stacked(),
        ];
    }
}
