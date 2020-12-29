<?php

declare(strict_types=1);

namespace App\Nova\Resources;

use Advoor\NovaEditorJs\NovaEditorJs;
use App\Helpers\Str;
use App\Models\Page as PageModel;
use Benjaminhirsch\NovaSlugField\Slug;
use Benjaminhirsch\NovaSlugField\TextWithSlug;
use DanielDeWit\NovaPaperclip\PaperclipImage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;

/**
 * Add page
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Page extends Resource
{
    /**
     * The model the resource corresponds to.
     * @var string
     */
    public static $model = PageModel::class;

    /**
     * The single value that should be used to represent the resource when being displayed.
     * @var string
     */
    public static $title = 'title';

    /**
     * Name of the group
     * @var string
     */
    public static $group = 'Content';

    /**
     * The columns that should be searched.
     * @var array
     */
    public static $search = [
        'title',
        'slug',
    ];

    /**
     * Get the displayable label of the resource.
     * @return string
     */
    public static function label()
    {
        return 'Pagina\'s';
    }

    /**
     * Get the displayable singular label of the resource.
     * @return string
     */
    public static function singularLabel()
    {
        return 'Pagina';
    }

    /**
     * Get the fields displayed by the resource.
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    // phpcs:ignore SlevomatCodingStandard.Functions.UnusedParameter
    public function fields(Request $request)
    {
        $user = $request->user();
        $groupRules = $user->can('admin', Page::class) ? 'nullable' : 'required';

        return [
            ID::make()->sortable(),

            TextWithSlug::make('Titel', 'title')->slug('slug'),
            Slug::make('Deelpad', 'slug')
                ->nullable(false)
                ->hideFromIndex()
                ->readonly(fn () => array_key_exists($this->slug, PageModel::REQUIRED_PAGES))
                ->rules([
                    Rule::unique('pages', 'slug')->where(function ($query) {
                        if ($this->id) {
                            $query = $query->where('id', '!=', $this->id);
                        }
                        if ($this->group === null) {
                            return $query->whereNull('group');
                        }
                        return $query->where('group', $this->group);
                    })
                ]),

            // Group
            Select::make('Groep', 'group')
                ->nullable()
                ->hideFromIndex()
                ->readonly(fn () => $this->exists)
                ->options(config('gumbo.page-groups')),

            // Group / Slug, for index
            Text::make('Pad', fn () => $this->group ? "{$this->group}/{$this->slug}" : $this->slug)
                ->hideWhenCreating()
                ->hideWhenUpdating(),

            // Add multi selects
            BelongsTo::make('Laatst bewerkt door', 'author', User::class)
                ->onlyOnDetail(),

            BelongsTo::make('Eigenaar', 'role', Role::class)
                ->help('Groep of commissie die deze pagina beheert')
                ->rules($groupRules)
                ->hideFromIndex()
                ->nullable(),

            // Show timestamps
            DateTime::make('Aangemaakt op', 'created_at')->onlyOnDetail(),
            DateTime::make('Laatst bewerkt op', 'created_at')->onlyOnDetail(),

            // Group / Slug, for index
            Text::make('Samenvatting', 'summary')
                ->nullable()
                ->rules('nullable', 'string', 'min:5', 'max:90')
                ->hideFromIndex()
                ->help('Wordt getoond op kaarten en in Google / Facebook'),

            PaperclipImage::make('Afbeelding', 'image')
                ->deletable()
                ->nullable()
                ->mimes(['png', 'jpeg', 'jpg'])
                ->help('Afbeelding die bij de activiteit en op Social Media getoond wordt. Maximaal 2MB')
                ->minWidth(640)
                ->minHeight(480)
                ->rules(
                    'nullable',
                    'image',
                    'mimes:jpeg,png',
                    'max:2048',
                    Rule::dimensions()->maxWidth(3840)->maxHeight(2140)
                ),

            // Add type
            Text::make('Type')->onlyOnDetail()->displayUsing(static fn($value) => Str::title($value)),

            // Add data
            NovaEditorJs::make('Inhoud', 'contents')->hideFromIndex()->stacked(),
        ];
    }
}
